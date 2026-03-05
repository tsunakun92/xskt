<?php

namespace App\Services\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

/**
 * Trait for handling common CRUD operations in services.
 */
trait HandlesCrudOperations {
    /**
     * Create a new model instance.
     *
     * @param  string  $modelClass
     * @param  Request|FormRequest  $request
     * @param  callable|null  $beforeCreate
     * @param  callable|null  $afterCreate
     * @return Model|null
     */
    protected function createModel(
        string $modelClass,
        Request|FormRequest $request,
        ?callable $beforeCreate = null,
        ?callable $afterCreate = null
    ): ?Model {
        return $this->handleTransaction(function () use ($modelClass, $request, $beforeCreate, $afterCreate) {
            $data = ($request instanceof FormRequest)
                ? $request->validated()
                : $request->only((new $modelClass)->getFillable());

            if ($beforeCreate) {
                $data = $beforeCreate($data) ?? $data;
            }

            $model = $modelClass::create($data);

            if (!$model) {
                throw new Exception('Failed to create model');
            }

            if ($afterCreate) {
                $afterCreate($model);
            }

            return $model;
        });
    }

    /**
     * Update an existing model instance.
     *
     * @param  Model  $model
     * @param  Request|FormRequest  $request
     * @param  callable|null  $beforeUpdate
     * @param  callable|null  $afterUpdate
     * @return bool
     */
    protected function updateModel(
        Model $model,
        Request|FormRequest $request,
        ?callable $beforeUpdate = null,
        ?callable $afterUpdate = null
    ): bool {
        $result = $this->handleTransaction(function () use ($model, $request, $beforeUpdate, $afterUpdate) {
            $data = ($request instanceof FormRequest)
                ? $request->validated()
                : $request->only($model->getFillable());

            if ($beforeUpdate) {
                $data = $beforeUpdate($data, $model) ?? $data;
            }

            $updated = $model->update($data);

            if (!$updated) {
                throw new Exception('Failed to update model');
            }

            if ($afterUpdate) {
                $afterUpdate($model);
            }

            return $updated;
        });

        return $result !== null;
    }

    /**
     * Delete a model instance.
     *
     * @param  Model  $model
     * @param  callable|null  $beforeDelete
     * @param  callable|null  $afterDelete
     * @return bool
     */
    protected function deleteModel(
        Model $model,
        ?callable $beforeDelete = null,
        ?callable $afterDelete = null
    ): bool {
        $result = $this->handleTransaction(function () use ($model, $beforeDelete, $afterDelete) {
            if ($beforeDelete) {
                $beforeDelete($model);
            }

            $deleted = $model->delete();

            if (!$deleted) {
                throw new Exception('Failed to delete model');
            }

            if ($afterDelete) {
                $afterDelete($model);
            }

            return $deleted;
        });

        return $result !== null;
    }
}
