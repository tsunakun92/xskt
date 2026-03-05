<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Validation\Rule;

class PrefectureRequest extends BaseAdminRequest {
    protected string $permissionBase = 'prefectures';

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        $id = $this->getRouteId(['prefecture']);

        return [
            'name'   => ['required', 'string', 'max:256', Rule::unique('prefectures', 'name')->ignore($id)],
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
            'name'   => __('admin::crud.prefectures.name'),
            'status' => __('admin::crud.prefectures.status'),
        ];
    }
}
