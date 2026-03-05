<?php

namespace Modules\Admin\Http\Requests;

use App\Entities\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Request class for PersonalAccessToken CRUD in Admin module.
 */
class PersonalAccessTokenRequest extends BaseAdminRequest {
    /**
     * Base permission name for this resource.
     *
     * @var string
     */
    protected string $permissionBase = 'personal-access-tokens';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        $id = $this->getRouteId(['personal_access_token', 'personal-access-token']);

        return [
            'name'           => ['required', 'string', 'max:255'],
            'tokenable_id'   => ['required', 'integer', 'exists:users,id'],
            'tokenable_type' => ['required', 'string', 'max:255'],
            'platform'       => SanctumPersonalAccessToken::getPlatformValidationRules(),
            'device_token'   => ['nullable', 'string', 'max:255'],
            'abilities'      => ['nullable', 'string'],
            'status'         => ['required', 'string', 'in:0,1'],
            'expires_at'     => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array {
        return [
            'name'           => __('admin::crud.personal-access-tokens.name'),
            'tokenable_id'   => __('admin::crud.personal-access-tokens.user_id'),
            'tokenable_type' => __('admin::crud.personal-access-tokens.tokenable_type'),
            'platform'       => __('admin::crud.personal-access-tokens.platform'),
            'device_token'   => __('admin::crud.personal-access-tokens.device_token'),
            'abilities'      => __('admin::crud.personal-access-tokens.abilities'),
            'status'         => __('admin::crud.personal-access-tokens.status'),
            'expires_at'     => __('admin::crud.personal-access-tokens.expires_at'),
        ];
    }
}
