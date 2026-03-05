<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Validation\Rule;

class UserRequest extends BaseAdminRequest {
    protected string $permissionBase = 'users';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        $id = $this->getRouteId(['user']);

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($id)],
            'password' => ['required', 'string', 'min:6'],
            'status'   => ['required', 'string', 'in:0,1,2'],
            'role_id'  => ['required', 'exists:roles,id'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array {
        return [
            'name'     => __('admin::crud.users.name'),
            'email'    => __('admin::crud.users.email'),
            'password' => __('admin::crud.users.password'),
            'username' => __('admin::crud.users.username'),
            'role_id'  => __('admin::crud.users.role_name'),
            'status'   => __('admin::crud.users.status'),
        ];
    }
}
