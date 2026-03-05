<?php

namespace Modules\Api\GraphQL\Resolvers;

use Exception;
use Illuminate\Support\Facades\Validator;

use App\Entities\Sanctum\PersonalAccessToken;
use App\Utils\CommonProcess;
use App\Utils\DateTimeExt;
use App\Utils\SqlHandler;
use Modules\Hr\Models\HrCompany;
use Modules\Logging\Utils\LogHandler;

class CompanyResolver {
    public function listCompanies($root, array $args) {
        // Validate input arguments
        $validator = Validator::make($args, [
            'page'     => 'nullable|integer|min:1',
            'limit'    => 'nullable|integer|min:1',
            'filter'   => 'nullable|string|max:255',
            'sort_by'  => 'nullable|string|max:255',
            'order'    => 'nullable|string|in:asc,desc',
            'version'  => 'required|string|max:255',
            'platform' => PersonalAccessToken::getPlatformValidationRules(),
        ]);

        if ($validator->fails()) {
            LogHandler::warning('Company list validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);

            return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
        }

        // Authenticate the request using Sanctum
        $user = auth('sanctum')->user();
        if (!$user) {
            LogHandler::warning('Company list failed: User not authenticated');

            return apiResponseError('User not authenticated');
        }

        // Set default values for pagination and sorting
        $page   = CommonProcess::getValue($args, 'page', 1);
        $limit  = CommonProcess::getValue($args, 'limit', 10);
        $filter = CommonProcess::getValue($args, 'filter', null);
        $sortBy = CommonProcess::getValue($args, 'sort_by', 'id');
        $order  = CommonProcess::getValue($args, 'order', 'asc');

        try {
            // Create base query for companies with related data
            $query = HrCompany::query();

            // Apply filters if any
            if ($filter) {
                $query->where(function ($q) use ($filter) {
                    $q->where('name', 'like', "%$filter%")
                        ->orWhere('email', 'like', "%$filter%")
                        ->orWhere('code', 'like', "%$filter%");
                });
            }

            // Apply sorting and pagination
            $query->orderBy($sortBy, $order);
            $total     = $query->count();
            $companies = $query->skip(($page - 1) * $limit)->take($limit)->get();

            $paginatorInfo = [
                'total'       => $total,
                'currentPage' => $page,
                'lastPage'    => ceil($total / $limit),
                'perPage'     => $limit,
            ];

            LogHandler::info('Companies list retrieved via API', [
                'total' => $total,
                'page'  => $page,
            ]);

            // Return a success response with company data
            return apiResponseSuccess('Companies retrieved successfully', [
                'data'          => $companies,
                'paginatorInfo' => $paginatorInfo,
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to retrieve companies via API', [
                'error' => $e->getMessage(),
            ]);

            return apiResponseError('Failed to retrieve companies');
        }
    }

    public function viewCompany($root, array $args) {
        // Validate input
        $validator = Validator::make($args, [
            'company_id' => 'required|integer',
            'version'    => 'required|string|max:255',
            'platform'   => PersonalAccessToken::getPlatformValidationRules(),
        ]);

        if ($validator->fails()) {
            LogHandler::warning('Company view validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);

            return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
        }

        // Authenticate the request using Sanctum
        $user = auth('sanctum')->user();
        if (!$user) {
            LogHandler::warning('Company view failed: User not authenticated');

            return apiResponseError('User not authenticated');
        }

        // Retrieve company by ID
        $company = HrCompany::find(CommonProcess::getValue($args, 'company_id'));

        if (!$company) {
            LogHandler::warning('Company not found via API', [
                'company_id' => CommonProcess::getValue($args, 'company_id'),
            ]);

            return apiResponseError('Company not found');
        }

        LogHandler::info('Company viewed via API', [
            'company_id' => $company->id,
        ]);

        // Return success response with company data
        return apiResponseSuccess('Company retrieved successfully', [
            'data' => $company,
        ]);
    }

    public function createCompany($root, array $args) {
        // Validate input
        $validator = Validator::make($args, [
            'code'      => 'nullable|string|min:13|max:13',
            'name'      => 'required|string|max:255',
            'phone'     => 'required|string|max:50',
            'email'     => 'required|string|email|max:255',
            'open_date' => 'required|string|date_format:d/m/Y',
            'address'   => 'required|string|max:255',
            'director'  => 'required|integer|max:255',
            'version'   => 'required|string|max:255',
            'platform'  => PersonalAccessToken::getPlatformValidationRules(),
        ]);

        if ($validator->fails()) {
            LogHandler::warning('Company creation validation failed', [
                'errors' => $validator->errors()->toArray(),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
        }

        // Format open_date string from d/m/Y to Y-m-d
        $args['open_date'] = DateTimeExt::convertDateTime(CommonProcess::getValue($args, 'open_date'), DateTimeExt::DATE_FORMAT_3, DateTimeExt::DATE_FORMAT_4);

        $company = null;
        $success = SqlHandler::handleTransaction(function () use (&$company, $args) {
            $company = HrCompany::create([
                'code'      => CommonProcess::getValue($args, 'code', CommonProcess::generateUniqId(13)),
                'name'      => CommonProcess::getValue($args, 'name'),
                'phone'     => CommonProcess::getValue($args, 'phone'),
                'email'     => CommonProcess::getValue($args, 'email'),
                'open_date' => CommonProcess::getValue($args, 'open_date'),
                'address'   => CommonProcess::getValue($args, 'address'),
                'director'  => CommonProcess::getValue($args, 'director'),
                'status'    => HrCompany::STATUS_ACTIVE,
            ]);

            return (bool) $company;
        });

        if (!$success || !$company) {
            LogHandler::error('Company creation failed - company not created', [
                'name' => CommonProcess::getValue($args, 'name'),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to create company');
        }

        LogHandler::info('Company created via API', [
            'company_id' => $company->id,
            'name'       => $company->name,
        ], LogHandler::CHANNEL_API);

        return apiResponseSuccess('Company created successfully', [
            'data' => $company,
        ]);
    }

    public function updateCompany($root, array $args) {
        try {
            // Validate input
            $validator = Validator::make($args, [
                'company_id' => 'required|integer|exists:hr_companies,id',
                'code'       => 'nullable|string|min:13|max:13|unique:hr_companies,code,' . CommonProcess::getValue($args, 'company_id'),
                'name'       => 'nullable|string|max:255',
                'phone'      => 'nullable|string|max:50',
                'email'      => 'nullable|string|email|max:255',
                'open_date'  => 'nullable|string|date_format:d/m/Y',
                'address'    => 'nullable|string|max:255',
                'director'   => 'nullable|integer|max:255',
                'status'     => 'nullable|integer',
                'version'    => 'required|string|max:255',
                'platform'   => PersonalAccessToken::getPlatformValidationRules(),
            ]);

            if ($validator->fails()) {
                return apiResponseError(message: 'Invalid input data: ' . getValidationErrorMessage($validator));
            }
        } catch (Exception $e) {
            // Handle validation exceptions (e.g., database table doesn't exist)
            $errorMessage = 'Invalid input data';
            if (str_contains($e->getMessage(), 'doesn\'t exist')) {
                $errorMessage = 'Invalid input data: The selected company id is invalid.';
            } elseif (isset($validator) && $validator->fails()) {
                $errorMessage = 'Invalid input data: ' . getValidationErrorMessage($validator);
            }

            return apiResponseError(message: $errorMessage);
        }

        if (CommonProcess::getValue($args, 'open_date', null)) {
            // Format open_date string from d/m/Y to Y-m-d
            $args['open_date'] = DateTimeExt::convertDateTime(CommonProcess::getValue($args, 'open_date'), DateTimeExt::DATE_FORMAT_3, DateTimeExt::DATE_FORMAT_4);
        }

        $company = HrCompany::find(CommonProcess::getValue($args, 'company_id'));

        if (!$company) {
            LogHandler::warning('Company update failed - company not found', [
                'company_id' => CommonProcess::getValue($args, 'company_id'),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Company not found');
        }

        $success = SqlHandler::handleTransaction(function () use ($company, $args) {
            $company->update($args);

            return true;
        });

        if (!$success) {
            LogHandler::error('Failed to update company via API', [
                'company_id' => CommonProcess::getValue($args, 'company_id'),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to update company');
        }

        LogHandler::info('Company updated via API', [
            'company_id' => $company->id,
            'name'       => $company->name,
        ], LogHandler::CHANNEL_API);

        return apiResponseSuccess('Company updated successfully', [
            'data' => $company,
        ]);
    }

    public function deleteCompany($root, array $args) {
        try {
            // Validate input
            $validator = Validator::make($args, [
                'company_id' => 'required|integer|exists:hr_companies,id',
                'version'    => 'required|string|max:255',
                'platform'   => PersonalAccessToken::getPlatformValidationRules(),
            ]);

            if ($validator->fails()) {
                return apiResponseError(message: 'Invalid input data: ' . getValidationErrorMessage($validator));
            }
        } catch (Exception $e) {
            // Handle validation exceptions (e.g., database table doesn't exist)
            $errorMessage = 'Invalid input data';
            if (str_contains($e->getMessage(), 'doesn\'t exist')) {
                $errorMessage = 'Invalid input data: The selected company id is invalid.';
            } elseif (isset($validator) && $validator->fails()) {
                $errorMessage = 'Invalid input data: ' . getValidationErrorMessage($validator);
            }

            return apiResponseError(message: $errorMessage);
        }

        try {
            // Find the company by ID
            $company = HrCompany::find(CommonProcess::getValue($args, 'company_id'));

            if (!$company) {
                return apiResponseError(message: 'Failed to delete company: Company not found');
            }

            // Delete the company
            $companyId   = $company->id;
            $companyName = $company->name;

            $success = SqlHandler::handleTransaction(function () use ($company) {
                $company->delete();

                return true;
            });

            if (!$success) {
                LogHandler::error('Failed to delete company via API', [
                    'company_id' => $companyId,
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Failed to delete company');
            }

            LogHandler::info('Company deleted via API', [
                'company_id' => $companyId,
                'name'       => $companyName,
            ], LogHandler::CHANNEL_API);

            // Return success response
            return apiResponseSuccess('Company deleted successfully');
        } catch (Exception $e) {
            LogHandler::error('Failed to delete company via API', [
                'company_id' => CommonProcess::getValue($args, 'company_id'),
                'error'      => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to delete company');
        }
    }
}
