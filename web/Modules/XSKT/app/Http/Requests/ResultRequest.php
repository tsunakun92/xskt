<?php

namespace Modules\XSKT\Http\Requests;

/**
 * Form request validation for Result.
 */
class ResultRequest extends BaseXsktRequest {
    protected string $permissionBase = 'results';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array {
        return [
            'draw_id'           => ['required', 'integer', 'exists:draws,id'],
            'prize_code'        => ['required', 'string', 'max:20'],
            'index_in_prize'    => ['required', 'integer', 'min:0'],
            'number'            => ['required', 'string', 'max:10'],
            'confirmed_by_rule' => ['required', 'boolean'],
            'status'            => ['required', 'string', 'in:0,1'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array {
        return [
            'draw_id'           => __('xskt::crud.results.draw_id'),
            'prize_code'        => __('xskt::crud.results.prize_code'),
            'index_in_prize'    => __('xskt::crud.results.index_in_prize'),
            'number'            => __('xskt::crud.results.number'),
            'confirmed_by_rule' => __('xskt::crud.results.confirmed_by_rule'),
            'status'            => __('xskt::crud.results.status'),
        ];
    }
}
