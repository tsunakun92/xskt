<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Validation\Rule;

class SettingRequest extends BaseAdminRequest {
    protected string $permissionBase = 'settings';

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        $id = $this->getRouteId(['setting']);

        return [
            'key'         => ['required', 'string', 'max:255', Rule::unique('settings', 'key')->ignore($id)],
            'value'       => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['required', 'string', 'in:0,1'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes() {
        return [
            'key'         => __('admin::crud.settings.key'),
            'value'       => __('admin::crud.settings.value'),
            'description' => __('admin::crud.settings.description'),
            'status'      => __('admin::crud.settings.status'),
        ];
    }
}
