<?php

namespace Modules\Api\GraphQL\Resolvers;

use Exception;
use Illuminate\Support\Facades\Validator;

use App\Entities\Sanctum\PersonalAccessToken;
use App\Utils\CommonProcess;
use Modules\Admin\Models\Setting;
use Modules\Admin\Models\User;
use Modules\Logging\Utils\LogHandler;

/**
 * Resolver for Setting Queries.
 */
class SettingResolver {
    /**
     * Get list of settings with pagination and filters.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function settingList($root, array $args): array {
        // Validate input arguments
        $validator = Validator::make($args, [
            'page'      => 'nullable|integer|min:1',
            'limit'     => 'nullable|integer|min:1',
            'filter'    => 'nullable|string|max:255',
            'sort_by'   => 'nullable|string|max:255',
            'order'     => 'nullable|string|in:asc,desc',
            'user_flag' => 'nullable|integer|in:0,1',
            'version'   => 'required|string|max:255',
            'platform'  => PersonalAccessToken::getPlatformValidationRules(),
        ]);

        if ($validator->fails()) {
            LogHandler::warning('Setting list validation failed', [
                'errors' => $validator->errors()->toArray(),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
        }

        // Authenticate the request using Sanctum
        $user = auth('sanctum')->user();
        if (!$user) {
            LogHandler::warning('Setting list failed: User not authenticated', [], LogHandler::CHANNEL_API);

            return apiResponseError('User not authenticated');
        }

        // Set default values for pagination and sorting
        $page     = CommonProcess::getValue($args, 'page', 1);
        $limit    = CommonProcess::getValue($args, 'limit', 10);
        $filter   = CommonProcess::getValue($args, 'filter', null);
        $sortBy   = CommonProcess::getValue($args, 'sort_by', 'updated_at');
        $order    = CommonProcess::getValue($args, 'order', 'desc');
        $userFlag = CommonProcess::getValue($args, 'user_flag', null);

        try {
            // Create base query for settings
            $query = Setting::query()->active();

            // Filter by user_flag if provided
            if ($userFlag !== null) {
                $query->where('user_flag', $userFlag);
            }

            // Apply filters if any
            if ($filter) {
                $query->where(function ($q) use ($filter) {
                    $q->where('key', 'like', "%{$filter}%")
                        ->orWhere('description', 'like', "%{$filter}%");
                });
            }

            // Map sort_by to actual column name
            $sortColumnMap = [
                'updated' => 'updated_at',
                'key'     => 'key',
                'id'      => 'id',
            ];
            $sortColumn = $sortColumnMap[$sortBy] ?? 'updated_at';

            // Apply sorting and pagination
            $query->orderBy($sortColumn, $order);
            $total    = $query->count();
            $settings = $query->skip(($page - 1) * $limit)->take($limit)->get();

            // Format settings for response
            $formattedSettings = $settings->map(function ($setting) {
                return [
                    'id'          => $setting->id,
                    'key'         => $setting->key,
                    'value'       => $setting->value,
                    'description' => $setting->description,
                    'user_flag'   => $setting->user_flag,
                    'status'      => $setting->status,
                    'updated'     => $setting->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            $paginatorInfo = [
                'total'       => $total,
                'currentPage' => $page,
                'lastPage'    => ceil($total / $limit),
                'perPage'     => $limit,
            ];

            LogHandler::info('Settings list retrieved via API', [
                'total' => $total,
                'page'  => $page,
            ], LogHandler::CHANNEL_API);

            return apiResponseSuccess('Settings retrieved successfully', [
                'data'          => $formattedSettings,
                'paginatorInfo' => $paginatorInfo,
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to retrieve settings via API', [
                'error' => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to retrieve settings');
        }
    }

    /**
     * Get setting details by key.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function settingView($root, array $args): array {
        // Validate input
        $validator = Validator::make($args, [
            'setting_key' => 'required|string|max:255',
            'version'     => 'required|string|max:255',
            'platform'    => PersonalAccessToken::getPlatformValidationRules(),
        ]);

        if ($validator->fails()) {
            LogHandler::warning('Setting view validation failed', [
                'errors' => $validator->errors()->toArray(),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
        }

        // Authenticate the request using Sanctum
        $user = auth('sanctum')->user();
        if (!$user) {
            LogHandler::warning('Setting view failed: User not authenticated', [], LogHandler::CHANNEL_API);

            return apiResponseError('User not authenticated');
        }

        try {
            // Retrieve setting by key
            $setting = Setting::where('key', $args['setting_key'])
                ->active()
                ->first();

            if (!$setting) {
                LogHandler::warning('Setting not found via API', [
                    'setting_key' => $args['setting_key'],
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Setting not found');
            }

            LogHandler::info('Setting viewed via API', [
                'setting_id' => $setting->id,
                'key'        => $setting->key,
            ], LogHandler::CHANNEL_API);

            // Format setting for response
            $formattedSetting = [
                'id'          => $setting->id,
                'key'         => $setting->key,
                'value'       => $setting->value,
                'description' => $setting->description,
                'user_flag'   => $setting->user_flag,
                'status'      => $setting->status,
                'updated'     => $setting->updated_at->format('Y-m-d H:i:s'),
            ];

            return apiResponseSuccess('Setting retrieved successfully', [
                'data' => $formattedSetting,
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to retrieve setting via API', [
                'setting_key' => $args['setting_key'] ?? null,
                'error'       => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to retrieve setting');
        }
    }

    /**
     * Get user-specific settings.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function settingByUser($root, array $args): array {
        // Validate input
        $validator = Validator::make($args, [
            'user_id'  => 'required|integer',
            'version'  => 'required|string|max:255',
            'platform' => PersonalAccessToken::getPlatformValidationRules(),
        ]);

        if ($validator->fails()) {
            LogHandler::warning('Setting by user validation failed', [
                'errors' => $validator->errors()->toArray(),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
        }

        // Authenticate the request using Sanctum
        $user = auth('sanctum')->user();
        if (!$user) {
            LogHandler::warning('Setting by user failed: User not authenticated', [], LogHandler::CHANNEL_API);

            return apiResponseError('User not authenticated');
        }

        try {
            // Retrieve user by ID
            $targetUser = User::find($args['user_id']);

            if (!$targetUser) {
                LogHandler::warning('User not found for setting by user API', [
                    'user_id' => $args['user_id'],
                ], LogHandler::CHANNEL_API);

                return apiResponseError('User not found');
            }

            // Get user settings (only user-visible settings with overrides)
            $configs = $targetUser->apiGetUserSettings();

            LogHandler::info('User settings retrieved via API', [
                'user_id' => $targetUser->id,
            ], LogHandler::CHANNEL_API);

            return apiResponseSuccess('User settings retrieved successfully', [
                'data' => $configs,
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to retrieve user settings via API', [
                'user_id' => $args['user_id'] ?? null,
                'error'   => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to retrieve user settings');
        }
    }
}
