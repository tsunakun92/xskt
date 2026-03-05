<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Utils\PolicyHelper;

/**
 * Form request for data deletion request submission
 */
class StoreDataDeletionRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        $supportedLanguages = PolicyHelper::getSupportedLanguages();

        return [
            'lang' => [
                'nullable',
                'string',
                Rule::in($supportedLanguages),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'lang.in' => __('validation.in', ['attribute' => 'lang']),
        ];
    }
}
