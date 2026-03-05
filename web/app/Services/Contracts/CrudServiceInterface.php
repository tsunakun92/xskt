<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

/**
 * Interface for CRUD service operations.
 * Defines contract for basic CRUD operations in services.
 */
interface CrudServiceInterface {
    /**
     * Create a new model instance.
     *
     * @param  Request|FormRequest  $request
     * @return Model|null
     */
    public function create(Request|FormRequest $request): ?Model;

    /**
     * Update an existing model instance.
     *
     * @param  Model  $model
     * @param  Request|FormRequest  $request
     * @return bool
     */
    public function update(Model $model, Request|FormRequest $request): bool;

    /**
     * Delete a model instance.
     *
     * @param  Model  $model
     * @return bool
     */
    public function delete(Model $model): bool;
}
