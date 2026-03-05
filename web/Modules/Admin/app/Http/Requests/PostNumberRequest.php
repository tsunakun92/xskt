<?php

namespace Modules\Admin\Http\Requests;

class PostNumberRequest extends BaseAdminRequest {
    protected string $permissionBase = 'post-numbers';

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        $id = $this->getRouteId(['post_number']);

        return [
            'post_number'     => ['required', 'string', 'max:256'],
            'name'            => ['required', 'string', 'max:256'],
            'municipality_id' => ['required', 'integer', 'exists:municipalities,id'],
            'status'          => ['required', 'string', 'in:0,1'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes() {
        return [
            'post_number'     => __('admin::crud.post_numbers.post_number'),
            'name'            => __('admin::crud.post_numbers.name'),
            'municipality_id' => __('admin::crud.post_numbers.municipality_id'),
            'status'          => __('admin::crud.post_numbers.status'),
        ];
    }
}
