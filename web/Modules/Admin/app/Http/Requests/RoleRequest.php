<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Validation\Rule;

class RoleRequest extends BaseAdminRequest {
    protected string $permissionBase = 'roles';

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        $id = $this->getRouteId(['role']);

        return [
            'name'   => ['required', 'string', 'max:255'],
            'code'   => ['required', 'string', 'max:255', Rule::unique('roles', 'code')->ignore($id)],
            'status' => ['required', 'string', 'in:0,1'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes() {
        return [
            'name'   => __('admin::crud.roles.name'),
            'code'   => __('admin::crud.roles.code'),
            'status' => __('admin::crud.roles.status'),
        ];
    }
}
