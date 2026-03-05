<?php

namespace Modules\Api\Http\Requests;

/**
 * Request class for ApiRequestLog resource.
 * Uses permission base to guard create and update actions.
 */
class ApiRequestLogRequest extends BaseApiRequest {
    /**
     * Permission base key.
     *
     * @var string
     */
    protected string $permissionBase = 'api-request-logs';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        // Api request logs are read-only via UI; no create/update rules required.
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array {
        return [
            'ip_address'     => __('api::crud.api-request-logs.ip_address'),
            'country'        => __('api::crud.api-request-logs.country'),
            'user_id'        => __('api::crud.api-request-logs.user_id'),
            'method'         => __('api::crud.api-request-logs.method'),
            'content'        => __('api::crud.api-request-logs.content'),
            'response'       => __('api::crud.api-request-logs.response'),
            'status'         => __('api::crud.api-request-logs.status'),
            'responsed_date' => __('api::crud.api-request-logs.responsed_date'),
        ];
    }
}
