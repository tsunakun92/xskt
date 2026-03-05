<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserPermissionRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        return [
            'permissions'   => 'array',
            'permissions.*' => 'string|exists:permissions,key',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return can_access('users.permission');
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes() {
        return [
            'permissions'   => __('admin::crud.users.permission'),
            'permissions.*' => __('admin::crud.users.permission'),
        ];
    }
}
