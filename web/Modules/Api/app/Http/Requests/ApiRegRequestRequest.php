<?php

namespace Modules\Api\Http\Requests;

class ApiRegRequestRequest extends BaseApiRequest {
    protected string $permissionBase = 'api-reg-requests';

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        $id = $this->getRouteId(['api-reg-request']);

        return [
            'email'    => ['required', 'email', 'max:256', 'unique:api_reg_requests,email,' . $id],
            'password' => ['required', 'string', 'min:8', 'max:256'],
            'status'   => ['required', 'string', 'in:0,1'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes() {
        return [
            'email'    => __('api::crud.api_reg_requests.email'),
            'password' => __('api::crud.api_reg_requests.password'),
            'status'   => __('api::crud.api_reg_requests.status'),
        ];
    }
}
