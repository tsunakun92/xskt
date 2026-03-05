<?php

namespace Modules\Admin\Http\Requests;

class MunicipalityRequest extends BaseAdminRequest {
    protected string $permissionBase = 'municipalities';

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        $id = $this->getRouteId(['municipality']);

        return [
            'name'          => ['required', 'string', 'max:256'],
            'prefecture_id' => ['required', 'integer', 'exists:prefectures,id'],
            'status'        => ['required', 'string', 'in:0,1'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes() {
        return [
            'name'          => __('admin::crud.municipalities.name'),
            'prefecture_id' => __('admin::crud.municipalities.prefecture_id'),
            'status'        => __('admin::crud.municipalities.status'),
        ];
    }
}
