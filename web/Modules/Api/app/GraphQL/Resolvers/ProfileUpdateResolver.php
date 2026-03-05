<?php

namespace Modules\Api\GraphQL\Resolvers;

use App\Entities\Sanctum\PersonalAccessToken;
use App\Utils\CommonProcess;
use Modules\Admin\Models\Role;
use Modules\Crm\Models\CrmCustomer;
use Modules\Hr\Models\HrProfile;

/**
 * Resolver for profile update mutation.
 */
class ProfileUpdateResolver extends BaseApiResolver {
    /**
     * Handle profile update for authenticated user.
     *
     * @param  mixed  $_
     * @param  array  $args
     * @return array
     */
    public function update($_, array $args): array {
        // Validate input
        $validationError = $this->validateInput($args, [
            'first_name'      => 'nullable|string|max:255',
            'last_name'       => 'nullable|string|max:255',
            'kana_first_name' => 'nullable|string|max:255',
            'kana_last_name'  => 'nullable|string|max:255',
            'phone_number'    => 'nullable|string|max:255',
            'address'         => 'nullable|string|max:255',
            'postal_code'     => 'nullable|string|size:7|regex:/^\d{7}$/',
            'ward'            => 'nullable|string|max:255',
            'chome'           => 'nullable|string|max:255',
            'ban'             => 'nullable|string|max:255',
            'go'              => 'nullable|string|max:255',
            'building'        => 'nullable|string|max:255',
            'room'            => 'nullable|string|max:255',
            'latitude'        => 'nullable|numeric',
            'longitude'       => 'nullable|numeric',
            'gender'          => 'nullable|integer|in:' . implode(',', array_keys(CommonProcess::getArrayGender())),
            'birthday'        => 'nullable|date|date_format:Y-m-d',
            'type'            => 'nullable|integer',
            'company_id'      => 'nullable|integer|exists:hr_companies,id',
            'version'         => 'required|string|max:255',
            'platform'        => PersonalAccessToken::getPlatformValidationRules(),
        ], 'Profile update', [
            'user_id' => auth()->id(),
        ]);

        if ($validationError !== null) {
            return $validationError;
        }

        // Rate limiting
        $rateLimitError = $this->checkRateLimitFromConfig(
            'default',
            'profile_update',
            'Profile update',
            [
                'user_id' => auth()->id(),
            ]
        );

        if ($rateLimitError !== null) {
            return $rateLimitError;
        }

        return $this->execute('Profile update', [
            'user_id' => auth()->id(),
        ], function () use ($args) {
            // Get authenticated user
            $user = auth()->user();

            if (!$user) {
                return apiResponseError('Access denied');
            }

            // Load role to determine profile type
            if (!$user->relationLoaded('rRole')) {
                $user->load('rRole');
            }

            $isCustomer = $user->rRole && $user->rRole->code === Role::ROLE_CUSTOMER_CODE;

            // Get profile based on role
            if ($isCustomer) {
                $profile = CrmCustomer::where('user_id', $user->id)->first();

                if (!$profile) {
                    return apiResponseError('Profile not found');
                }

                // Prepare update data (only customer-specific fields)
                $updateData = $this->prepareCustomerUpdateData($args);
            } else {
                $profile = HrProfile::where('user_id', $user->id)->first();

                if (!$profile) {
                    return apiResponseError('Profile not found');
                }

                // Prepare update data (only staff-specific fields)
                $updateData = $this->prepareStaffUpdateData($args);
            }

            // Update profile with provided fields
            foreach ($updateData as $key => $value) {
                $profile->{$key} = $value;
            }

            // Save profile (address_line will be built automatically in model boot)
            $profile->save();

            // Reload profile with relationships based on profile type
            $profile->load('rUser');
            if ($isCustomer) {
                // CrmCustomer doesn't have rCompany relationship
            } else {
                // HrProfile has rCompany relationship
                $profile->load('rCompany');
            }

            return apiResponseSuccess('Profile updated successfully', [
                'data' => $profile,
            ]);
        });
    }

    /**
     * Prepare update data for customer profile.
     *
     * @param  array  $args
     * @return array
     */
    private function prepareCustomerUpdateData(array $args): array {
        $updateData = [];

        $customerFields = [
            'first_name',
            'last_name',
            'kana_first_name',
            'kana_last_name',
            'phone_number',
            'address',
            'postal_code',
            'ward',
            'chome',
            'ban',
            'go',
            'building',
            'room',
            'latitude',
            'longitude',
            'gender',
            'birthday',
            'type',
        ];

        foreach ($customerFields as $field) {
            if (array_key_exists($field, $args)) {
                $updateData[$field] = $args[$field];
            }
        }

        return $updateData;
    }

    /**
     * Prepare update data for staff profile.
     *
     * @param  array  $args
     * @return array
     */
    private function prepareStaffUpdateData(array $args): array {
        $updateData = [];

        $staffFields = [
            'first_name',
            'last_name',
            'kana_first_name',
            'kana_last_name',
            'phone_number',
            'address',
            'postal_code',
            'ward',
            'gender',
            'birthday',
            'company_id',
        ];

        foreach ($staffFields as $field) {
            if (array_key_exists($field, $args)) {
                $updateData[$field] = $args[$field];
            }
        }

        return $updateData;
    }
}
