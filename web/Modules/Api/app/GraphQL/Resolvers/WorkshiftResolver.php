<?php

namespace Modules\Api\GraphQL\Resolvers;

use Exception;
use Illuminate\Support\Facades\Validator;

use App\Entities\Sanctum\PersonalAccessToken;
use App\Utils\CommonProcess;
use App\Utils\SqlHandler;
use Modules\Hr\Models\HrWorkShift;
use Modules\Logging\Utils\LogHandler;

class WorkshiftResolver {
    public function listWorkshift($root, array $args) {
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
            LogHandler::warning('Workshift list validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);

            return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
        }

        // Authenticate the request using Sanctum
        $user = auth('sanctum')->user();
        if (!$user) {
            LogHandler::warning('Workshift list failed: User not authenticated');

            return apiResponseError('User not authenticated');
        }

        // Set default values for pagination and sorting
        $page   = CommonProcess::getValue($args, 'page', 1);
        $limit  = CommonProcess::getValue($args, 'limit', 10);
        $filter = CommonProcess::getValue($args, 'filter', null);
        $sortBy = CommonProcess::getValue($args, 'sort_by', 'id');
        $order  = CommonProcess::getValue($args, 'order', 'asc');

        try {
            // Create base query for workshifts with related data
            $query = HrWorkShift::with(['rRole', 'rCompany']);

            // Apply filters if any
            if ($filter) {
                $query->where(function ($q) use ($filter) {
                    $q->where('name', 'like', "%$filter%")
                        ->orWhere('description', 'like', "%$filter%")
                        ->orWhere('code', 'like', "%$filter%");
                });
            }

            // Apply sorting and pagination
            $query->orderBy($sortBy, $order);
            $total      = $query->count();
            $workshifts = $query->skip(($page - 1) * $limit)->take($limit)->get();

            $paginatorInfo = [
                'total'       => $total,
                'currentPage' => $page,
                'lastPage'    => ceil($total / $limit),
                'perPage'     => $limit,
            ];

            LogHandler::info('Workshifts list retrieved via API', [
                'total' => $total,
                'page'  => $page,
            ]);

            // Return a success response with workshift data
            return apiResponseSuccess('Workshifts retrieved successfully', [
                'data'          => $workshifts,
                'paginatorInfo' => $paginatorInfo,
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to retrieve workshifts via API', [
                'error' => $e->getMessage(),
            ]);

            return apiResponseError('Failed to retrieve workshifts');
        }
    }

    public function viewWorkshift($root, array $args) {
        try {
            // Validate input
            $validator = Validator::make($args, [
                'workshift_id' => 'required|integer',
                'version'      => 'required|string|max:255',
                'platform'     => PersonalAccessToken::getPlatformValidationRules(),
            ]);

            if ($validator->fails()) {
                LogHandler::warning('Workshift view validation failed', [
                    'errors' => $validator->errors()->toArray(),
                ]);

                return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
            }

            // Authenticate the request using Sanctum
            $user = auth('sanctum')->user();
            if (!$user) {
                LogHandler::warning('Workshift view failed: User not authenticated');

                return apiResponseError('User not authenticated');
            }

            // Retrieve workshift by ID
            $workshift = HrWorkShift::with(['rRole', 'rCompany'])->find($args['workshift_id']);

            if (!$workshift) {
                LogHandler::warning('Workshift not found via API', [
                    'workshift_id' => $args['workshift_id'] ?? null,
                ]);

                return apiResponseError('Workshift not found');
            }

            LogHandler::info('Workshift viewed via API', [
                'workshift_id' => $workshift->id,
            ]);

            // Return success response with workshift data
            return apiResponseSuccess('Workshift retrieved successfully', [
                'data' => $workshift,
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to view workshift via API', [
                'error'        => $e->getMessage(),
                'workshift_id' => $args['workshift_id'] ?? null,
            ]);

            return apiResponseError('Failed to retrieve workshift');
        }
    }

    public function createWorkshift($root, array $args) {
        try {
            // Validate input
            $validator = Validator::make($args, [
                'code'             => 'nullable|string|min:13|max:13|unique:hr_work_shifts,code',
                'name'             => 'required|string|max:255',
                'description'      => 'required|string|max:255',
                'start'            => 'required|string|date_format:H:i',
                'end'              => 'required|string|date_format:H:i',
                'company_id'       => 'required|integer|exists:hr_companies,id',
                'role_id'          => 'required|integer|exists:roles,id',
                'max_employee_cnt' => 'required|integer|min:1',
                'color'            => 'required|string|regex:/^[0-9A-Fa-f]{6}$/',
                'version'          => 'required|string|max:255',
                'platform'         => PersonalAccessToken::getPlatformValidationRules(),
            ]);

            if ($validator->fails()) {
                LogHandler::warning('Workshift creation validation failed', [
                    'errors' => $validator->errors()->toArray(),
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
            }
        } catch (Exception $e) {
            // Handle validation exceptions (e.g., database table doesn't exist)
            $errorMessage = 'Invalid input data';
            if (str_contains($e->getMessage(), 'doesn\'t exist')) {
                if (str_contains($e->getMessage(), 'hr_companies')) {
                    $errorMessage = 'Invalid input data: The selected company id is invalid.';
                } elseif (str_contains($e->getMessage(), 'roles')) {
                    $errorMessage = 'Invalid input data: The selected role id is invalid.';
                }
            } elseif (isset($validator) && $validator->fails()) {
                $errorMessage = 'Invalid input data: ' . getValidationErrorMessage($validator);
            }
            LogHandler::error('Workshift creation validation exception', [
                'error' => $e->getMessage(),
            ]);

            return apiResponseError($errorMessage);
        }

        $workshift = null;
        $success   = SqlHandler::handleTransaction(function () use (&$workshift, $args) {
            $workshift = HrWorkShift::create([
                'code'             => CommonProcess::getValue($args, 'code', CommonProcess::generateUniqId(13)),
                'name'             => CommonProcess::getValue($args, 'name'),
                'description'      => CommonProcess::getValue($args, 'description'),
                'start'            => CommonProcess::getValue($args, 'start'),
                'end'              => CommonProcess::getValue($args, 'end'),
                'company_id'       => CommonProcess::getValue($args, 'company_id'),
                'role_id'          => CommonProcess::getValue($args, 'role_id'),
                'max_employee_cnt' => CommonProcess::getValue($args, 'max_employee_cnt'),
                'color'            => CommonProcess::getValue($args, 'color'),
                'status'           => HrWorkShift::STATUS_ACTIVE,
            ]);

            return (bool) $workshift;
        });

        if (!$success || !$workshift) {
            LogHandler::error('Workshift creation failed - workshift not created', [
                'name' => CommonProcess::getValue($args, 'name'),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to create workshift');
        }

        LogHandler::info('Workshift created via API', [
            'workshift_id' => $workshift->id,
            'name'         => $workshift->name,
        ], LogHandler::CHANNEL_API);

        return apiResponseSuccess('Workshift created successfully', [
            'data' => $workshift->load(['rRole', 'rCompany']),
        ]);
    }

    public function updateWorkshift($root, array $args) {
        try {
            // Validate input
            $validator = Validator::make($args, [
                'workshift_id'     => 'required|integer|exists:hr_work_shifts,id',
                'code'             => 'nullable|string|min:13|max:13|unique:hr_work_shifts,code,' . CommonProcess::getValue($args, 'workshift_id'),
                'name'             => 'nullable|string|max:255',
                'description'      => 'nullable|string|max:255',
                'start'            => 'nullable|string|date_format:H:i',
                'end'              => 'nullable|string|date_format:H:i',
                'company_id'       => 'nullable|integer|exists:hr_companies,id',
                'role_id'          => 'nullable|integer|exists:roles,id',
                'max_employee_cnt' => 'nullable|integer|min:1',
                'color'            => 'nullable|string|regex:/^[0-9A-Fa-f]{6}$/',
                'status'           => 'nullable|integer',
                'version'          => 'required|string|max:255',
                'platform'         => PersonalAccessToken::getPlatformValidationRules(),
            ]);

            if ($validator->fails()) {
                return apiResponseError(message: 'Invalid input data: ' . getValidationErrorMessage($validator));
            }
        } catch (Exception $e) {
            // Handle validation exceptions (e.g., database table doesn't exist)
            $errorMessage = 'Invalid input data';
            if (str_contains($e->getMessage(), 'doesn\'t exist')) {
                $errorMessage = 'Invalid input data: The selected workshift id is invalid.';
            } elseif (isset($validator) && $validator->fails()) {
                $errorMessage = 'Invalid input data: ' . getValidationErrorMessage($validator);
            }

            return apiResponseError(message: $errorMessage);
        }

        $workshift = HrWorkShift::find(CommonProcess::getValue($args, 'workshift_id'));

        if (!$workshift) {
            LogHandler::warning('Workshift update failed - workshift not found', [
                'workshift_id' => CommonProcess::getValue($args, 'workshift_id'),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Workshift not found');
        }

        $success = SqlHandler::handleTransaction(function () use ($workshift, $args) {
            $workshift->update($args);

            return true;
        });

        if (!$success) {
            LogHandler::error('Failed to update workshift via API', [
                'workshift_id' => CommonProcess::getValue($args, 'workshift_id'),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to update workshift');
        }

        LogHandler::info('Workshift updated via API', [
            'workshift_id' => $workshift->id,
            'name'         => $workshift->name,
        ], LogHandler::CHANNEL_API);

        return apiResponseSuccess('Workshift updated successfully', [
            'data' => $workshift->load(['rRole', 'rCompany']),
        ]);
    }

    public function deleteWorkshift($root, array $args) {
        try {
            // Validate input
            $validator = Validator::make($args, [
                'workshift_id' => 'required|integer|exists:hr_work_shifts,id',
                'version'      => 'required|string|max:255',
                'platform'     => PersonalAccessToken::getPlatformValidationRules(),
            ]);

            if ($validator->fails()) {
                LogHandler::warning('Workshift delete validation failed', [
                    'workshift_id' => CommonProcess::getValue($args, 'workshift_id'),
                    'errors'       => $validator->errors()->toArray(),
                ]);

                return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
            }
        } catch (Exception $e) {
            // Handle validation exceptions (e.g., database table doesn't exist)
            $errorMessage = 'Invalid input data';
            if (str_contains($e->getMessage(), 'doesn\'t exist')) {
                $errorMessage = 'Invalid input data: The selected workshift id is invalid.';
            } elseif (isset($validator) && $validator->fails()) {
                $errorMessage = 'Invalid input data: ' . getValidationErrorMessage($validator);
            }
            LogHandler::error('Workshift delete validation exception', [
                'workshift_id' => CommonProcess::getValue($args, 'workshift_id'),
                'error'        => $e->getMessage(),
            ]);

            return apiResponseError($errorMessage);
        }

        $workshift = HrWorkShift::find(CommonProcess::getValue($args, 'workshift_id'));

        if (!$workshift) {
            LogHandler::warning('Workshift delete failed - workshift not found', [
                'workshift_id' => CommonProcess::getValue($args, 'workshift_id'),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Workshift not found');
        }

        $workshiftId   = $workshift->id;
        $workshiftName = $workshift->name;

        $success = SqlHandler::handleTransaction(function () use ($workshift) {
            $workshift->delete();

            return true;
        });

        if (!$success) {
            LogHandler::error('Failed to delete workshift via API', [
                'workshift_id' => $workshiftId,
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to delete workshift');
        }

        LogHandler::info('Workshift deleted via API', [
            'workshift_id' => $workshiftId,
            'name'         => $workshiftName,
        ], LogHandler::CHANNEL_API);

        return apiResponseSuccess('Workshift deleted successfully');
    }
}
