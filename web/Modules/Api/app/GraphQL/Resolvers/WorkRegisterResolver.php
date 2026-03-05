<?php

namespace Modules\Api\GraphQL\Resolvers;

use Exception;
use Illuminate\Support\Facades\Validator;

use App\Entities\Sanctum\PersonalAccessToken;
use App\Utils\CommonProcess;
use App\Utils\DateTimeExt;
use Modules\Hr\Models\HrWorkRegister;
use Modules\Logging\Utils\LogHandler;

class WorkRegisterResolver {
    public function listWorkRegister($root, array $args) {
        // Validate input arguments
        $validator = Validator::make($args, [
            'employee_id' => 'nullable|integer|exists:users,id',
            'group_id'    => 'nullable|integer',
            'shift_id'    => 'nullable|integer|exists:hr_work_shifts,id',
            'start_date'  => 'nullable|string|date_format:d/m/Y',
            'end_date'    => 'nullable|string|date_format:d/m/Y',
            'page'        => 'nullable|integer|min:1',
            'limit'       => 'nullable|integer|min:1',
            'filter'      => 'nullable|string|max:255',
            'sort_by'     => 'nullable|string|max:255',
            'order'       => 'nullable|string|in:asc,desc',
            'version'     => 'required|string|max:255',
            'platform'    => PersonalAccessToken::getPlatformValidationRules(),
        ]);

        if ($validator->fails()) {
            return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
        }

        // Authenticate the request using Sanctum
        $user = auth('sanctum')->user();
        if (!$user) {
            return apiResponseError('User not authenticated');
        }

        // Set default values for pagination and sorting
        $page       = CommonProcess::getValue($args, 'page', 1);
        $limit      = CommonProcess::getValue($args, 'limit', 10);
        $filter     = CommonProcess::getValue($args, 'filter', null);
        $sortBy     = CommonProcess::getValue($args, 'sort_by', 'id');
        $order      = CommonProcess::getValue($args, 'order', 'asc');
        $employeeId = CommonProcess::getValue($args, 'employee_id', null);
        $groupId    = CommonProcess::getValue($args, 'group_id', null);
        $shiftId    = CommonProcess::getValue($args, 'shift_id', null);
        $startDate  = CommonProcess::getValue($args, 'start_date', null);
        $endDate    = CommonProcess::getValue($args, 'end_date', null);

        try {
            // Create base query for work registers with related data
            $query = HrWorkRegister::with(['rShift', 'rEmployee']);

            // Apply filtering based on arguments
            if ($employeeId) {
                $query->where('employee_id', $employeeId);
            }
            if ($groupId) {
                $query->where('group_id', $groupId);
            }
            if ($shiftId) {
                $query->where('shift_id', $shiftId);
            }
            if ($startDate) {
                $startDate = DateTimeExt::convertDateTime($startDate, DateTimeExt::DATE_FORMAT_3, DateTimeExt::DATE_FORMAT_4);
                $query->whereDate('date', '>=', $startDate);
            }
            if ($endDate) {
                $endDate = DateTimeExt::convertDateTime($endDate, DateTimeExt::DATE_FORMAT_3, DateTimeExt::DATE_FORMAT_4);
                $query->whereDate('date', '<=', $endDate);
            }
            if ($filter) {
                $query->where(function ($q) use ($filter) {
                    $q->where('name', 'like', "%$filter%")
                        ->orWhere('description', 'like', "%$filter%");
                });
            }

            // Apply sorting and pagination
            $query->orderBy($sortBy, $order);
            $total         = $query->count();
            $workRegisters = $query->skip(($page - 1) * $limit)->take($limit)->get();

            $paginatorInfo = [
                'total'       => $total,
                'currentPage' => $page,
                'lastPage'    => ceil($total / $limit),
                'perPage'     => $limit,
            ];

            LogHandler::info('Work registers list retrieved via API', [
                'total' => $total,
                'page'  => $page,
            ], LogHandler::CHANNEL_API);

            // Return a success response with work register data
            return apiResponseSuccess('Work registers retrieved successfully', [
                'data'          => $workRegisters,
                'paginatorInfo' => $paginatorInfo,
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to retrieve work registers via API', [
                'error' => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to retrieve work registers');
        }
    }

    public function viewWorkRegister($root, array $args) {
        try {
            // Validate input
            $validator = Validator::make($args, [
                'work_register_id' => 'required|integer',
                'version'          => 'required|string|max:255',
                'platform'         => PersonalAccessToken::getPlatformValidationRules(),
            ]);

            if ($validator->fails()) {
                LogHandler::warning('Work register view validation failed', [
                    'errors' => $validator->errors()->toArray(),
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
            }

            // Authenticate the request using Sanctum
            $user = auth('sanctum')->user();
            if (!$user) {
                LogHandler::warning('Work register view failed: User not authenticated', [], LogHandler::CHANNEL_API);

                return apiResponseError('User not authenticated');
            }

            // Retrieve workRegister by ID
            $workRegister = HrWorkRegister::with(['rShift', 'rEmployee'])->find(CommonProcess::getValue($args, 'work_register_id'));

            if (!$workRegister) {
                LogHandler::warning('Work register not found via API', [
                    'work_register_id' => CommonProcess::getValue($args, 'work_register_id'),
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Work register not found');
            }

            LogHandler::info('Work register viewed via API', [
                'work_register_id' => $workRegister->id,
            ], LogHandler::CHANNEL_API);

            // Return success response with workRegister data
            return apiResponseSuccess('Work register retrieved successfully', [
                'data' => $workRegister,
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to view work register via API', [
                'error'            => $e->getMessage(),
                'work_register_id' => CommonProcess::getValue($args, 'work_register_id'),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to retrieve work register');
        }
    }
}
