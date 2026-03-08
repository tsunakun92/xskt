<?php

namespace Modules\XSKT\Http\Requests;

/**
 * Form request validation for Draw.
 */
class DrawRequest extends BaseXsktRequest {
    protected string $permissionBase = 'draws';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array {
        return [
            'region'        => ['required', 'string', 'in:MB,MT,MN'],
            'province_code' => ['nullable', 'string', 'max:10'],
            'station_code'  => ['nullable', 'string', 'max:10'],
            'draw_date'     => ['required', 'date'],

            'confirmed_at'  => ['nullable', 'date'],
            'status'        => ['required', 'string', 'in:0,1'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array {
        return [
            'region'        => __('xskt::crud.draws.region'),
            'province_code' => __('xskt::crud.draws.province_code'),
            'station_code'  => __('xskt::crud.draws.station_code'),
            'draw_date'     => __('xskt::crud.draws.draw_date'),

            'confirmed_at'  => __('xskt::crud.draws.confirmed_at'),
            'status'        => __('xskt::crud.draws.status'),
        ];
    }
}
