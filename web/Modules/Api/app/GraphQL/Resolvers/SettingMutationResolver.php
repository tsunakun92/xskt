<?php

namespace Modules\Api\GraphQL\Resolvers;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Entities\Sanctum\PersonalAccessToken;
use App\Utils\CommonProcess;
use App\Utils\SqlHandler;
use Modules\Admin\Models\Setting;
use Modules\Logging\Utils\LogHandler;

/**
 * Resolver for Setting Mutations.
 */
class SettingMutationResolver {
    /**
     * Create a new setting.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function settingCreate($root, array $args): array {
        // Validate input
        $validator = Validator::make($args, [
            'key'         => 'required|string|max:255|unique:settings,key',
            'value'       => 'required|string',
            'description' => 'nullable|string',
            'user_flag'   => 'nullable|integer|in:0,1',
            'version'     => 'required|string|max:255',
            'platform'    => PersonalAccessToken::getPlatformValidationRules(),
        ]);

        if ($validator->fails()) {
            LogHandler::warning('Setting creation validation failed', [
                'errors' => $validator->errors()->toArray(),
            ], LogHandler::CHANNEL_API);

            $errorMessage = 'Invalid input data';
            if ($validator->errors()->has('key') && str_contains($validator->errors()->first('key'), 'taken')) {
                $errorMessage = 'Failed to create setting: Key already exists';
            } else {
                $errorMessage = 'Invalid input data: ' . getValidationErrorMessage($validator);
            }

            return apiResponseError($errorMessage);
        }

        // Authenticate the request using Sanctum
        $user = auth('sanctum')->user();
        if (!$user) {
            LogHandler::warning('Setting creation failed: User not authenticated', [], LogHandler::CHANNEL_API);

            return apiResponseError('User not authenticated');
        }

        try {
            $setting = null;
            $success = SqlHandler::handleTransaction(function () use (&$setting, $args, $user) {
                $setting = Setting::create([
                    'key'         => $args['key'],
                    'value'       => $args['value'],
                    'description' => CommonProcess::getValue($args, 'description', null),
                    'user_flag'   => CommonProcess::getValue($args, 'user_flag', 0),
                    'status'      => Setting::STATUS_ACTIVE,
                    'created_by'  => $user->id,
                ]);

                return (bool) $setting;
            });

            if (!$success || !$setting) {
                LogHandler::error('Setting creation failed - setting not created', [
                    'key' => $args['key'],
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Failed to create setting');
            }

            LogHandler::info('Setting created via API', [
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

            return apiResponseSuccess('Setting created successfully', [
                'data' => $formattedSetting,
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to create setting via API', [
                'key'   => $args['key'] ?? null,
                'error' => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to create setting');
        }
    }

    /**
     * Update an existing setting.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function settingUpdate($root, array $args): array {
        // Validate input
        $validator = Validator::make($args, [
            'key'         => 'required|string|max:255|exists:settings,key',
            'value'       => 'required|string',
            'description' => 'nullable|string',
            'user_flag'   => 'nullable|integer|in:0,1',
            'version'     => 'required|string|max:255',
            'platform'    => PersonalAccessToken::getPlatformValidationRules(),
        ]);

        if ($validator->fails()) {
            LogHandler::warning('Setting update validation failed', [
                'errors' => $validator->errors()->toArray(),
            ], LogHandler::CHANNEL_API);

            $errorMessage = 'Invalid input data';
            if ($validator->errors()->has('key') && str_contains($validator->errors()->first('key'), 'not exist')) {
                $errorMessage = 'Failed to update setting: Key not found';
            } else {
                $errorMessage = 'Invalid input data: ' . getValidationErrorMessage($validator);
            }

            return apiResponseError($errorMessage);
        }

        // Authenticate the request using Sanctum
        $user = auth('sanctum')->user();
        if (!$user) {
            LogHandler::warning('Setting update failed: User not authenticated', [], LogHandler::CHANNEL_API);

            return apiResponseError('User not authenticated');
        }

        try {
            // Retrieve setting by key
            $setting = Setting::where('key', $args['key'])->first();

            if (!$setting) {
                LogHandler::warning('Setting update failed - setting not found', [
                    'key' => $args['key'],
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Failed to update setting: Key not found');
            }

            $success = SqlHandler::handleTransaction(function () use ($setting, $args) {
                $updateData = [
                    'value' => $args['value'],
                ];

                if (isset($args['description'])) {
                    $updateData['description'] = $args['description'];
                }

                if (isset($args['user_flag'])) {
                    $updateData['user_flag'] = $args['user_flag'];
                }

                $setting->update($updateData);

                return true;
            });

            if (!$success) {
                LogHandler::error('Failed to update setting via API', [
                    'key' => $args['key'],
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Failed to update setting');
            }

            LogHandler::info('Setting updated via API', [
                'setting_id' => $setting->id,
                'key'        => $setting->key,
            ], LogHandler::CHANNEL_API);

            // Refresh setting to get updated data
            $setting->refresh();

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

            return apiResponseSuccess('Setting updated successfully', [
                'data' => $formattedSetting,
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to update setting via API', [
                'key'   => $args['key'] ?? null,
                'error' => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to update setting');
        }
    }

    /**
     * Delete an existing setting.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return array
     */
    public function settingDelete($root, array $args): array {
        // Validate input
        $validator = Validator::make($args, [
            'key'      => 'required|string|max:255|exists:settings,key',
            'version'  => 'required|string|max:255',
            'platform' => PersonalAccessToken::getPlatformValidationRules(),
        ]);

        if ($validator->fails()) {
            LogHandler::warning('Setting delete validation failed', [
                'errors' => $validator->errors()->toArray(),
            ], LogHandler::CHANNEL_API);

            $errorMessage = 'Invalid input data';
            if ($validator->errors()->has('key') && str_contains($validator->errors()->first('key'), 'not exist')) {
                $errorMessage = 'Failed to delete setting: Key not found';
            } else {
                $errorMessage = 'Invalid input data: ' . getValidationErrorMessage($validator);
            }

            return apiResponseError($errorMessage);
        }

        // Authenticate the request using Sanctum
        $user = auth('sanctum')->user();
        if (!$user) {
            LogHandler::warning('Setting delete failed: User not authenticated', [], LogHandler::CHANNEL_API);

            return apiResponseError('User not authenticated');
        }

        try {
            // Retrieve setting by key
            $setting = Setting::where('key', $args['key'])->first();

            if (!$setting) {
                LogHandler::warning('Setting delete failed - setting not found', [
                    'key' => $args['key'],
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Failed to delete setting: Key not found');
            }

            $settingId  = $setting->id;
            $settingKey = $setting->key;

            $success = SqlHandler::handleTransaction(function () use ($settingId) {
                // Delete all user overrides for this setting
                DB::table('user_settings')
                    ->where('setting_id', $settingId)
                    ->delete();

                // Delete the setting
                Setting::where('id', $settingId)->delete();

                return true;
            });

            if (!$success) {
                LogHandler::error('Failed to delete setting via API', [
                    'key' => $args['key'],
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Failed to delete setting');
            }

            LogHandler::info('Setting deleted via API', [
                'setting_id' => $settingId,
                'key'        => $settingKey,
            ], LogHandler::CHANNEL_API);

            return apiResponseSuccess('Setting deleted successfully');
        } catch (Exception $e) {
            LogHandler::error('Failed to delete setting via API', [
                'key'   => $args['key'] ?? null,
                'error' => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to delete setting');
        }
    }
}
