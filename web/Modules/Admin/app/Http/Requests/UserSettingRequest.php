<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserSettingRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        return [
            'settings'   => 'array',
            'settings.*' => 'nullable|string|max:65535', // text field can be large
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        // Chỉ cho phép khi user có quyền hoặc tự cập nhật chính mình (nếu có route id)
        $currentUser = auth()->user();
        if (!$currentUser) {
            return false;
        }

        $targetUserId = $this->route('id');

        return $currentUser->id === (int) $targetUserId || can_access('users.setting');
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes() {
        return [
            'settings'   => __('admin::crud.users.setting'),
            'settings.*' => __('admin::crud.users.setting'),
        ];
    }
}
