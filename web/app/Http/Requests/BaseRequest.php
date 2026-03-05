<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base request with common authorize logic per CRUD action.
 * This is a shared base class for all modules to avoid inter-module dependencies.
 */
abstract class BaseRequest extends FormRequest {
    /**
     * Permission base key, e.g. 'users', 'roles', 'crm.bookings'.
     *
     * @var string
     */
    protected string $permissionBase = '';

    /**
     * Map HTTP verb to permission action.
     *
     * @return bool
     */
    public function authorize(): bool {
        if ($this->permissionBase === '') {
            return false;
        }

        if ($this->isMethod('post')) {
            return can_access("{$this->permissionBase}.create");
        }

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return can_access("{$this->permissionBase}.edit");
        }

        return false;
    }

    /**
     * Get resource ID from route parameters.
     * This helps building rules like Rule::unique()->ignore($id) for update actions.
     *
     * @param  array<int, string>  $parameterKeys  Route parameter keys to try (e.g. ['user', 'booking'])
     * @return int Resource ID, 0 if not found
     */
    protected function getRouteId(array $parameterKeys): int {
        foreach ($parameterKeys as $key) {
            $value = $this->route($key);

            if ($value === null && is_string($key) && str_contains($key, '-')) {
                $value = $this->route(str_replace('-', '_', $key));
            }

            if (is_numeric($value)) {
                return (int) $value;
            }

            if (is_object($value) && method_exists($value, 'getKey')) {
                $id = $value->getKey();
                if (is_numeric($id)) {
                    return (int) $id;
                }
            }
        }

        $fallback = $this->route('id');
        if (is_numeric($fallback)) {
            return (int) $fallback;
        }

        return 0;
    }
}
