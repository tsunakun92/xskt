<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Validation\Rule;

class PermissionRequest extends BaseAdminRequest {
    protected string $permissionBase = 'permissions';

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        $id = $this->getRouteId(['permission']);

        return [
            'name'   => ['required', 'string', 'max:255'],
            'key'    => ['required', 'string', 'max:255', Rule::unique('permissions', 'key')->ignore($id)],
            'group'  => ['required', 'string', 'max:255'],
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
            'name'   => __('admin::crud.permissions.name'),
            'key'    => __('admin::crud.permissions.key'),
            'group'  => __('admin::crud.permissions.group'),
            'status' => __('admin::crud.permissions.status'),
        ];
    }
}
