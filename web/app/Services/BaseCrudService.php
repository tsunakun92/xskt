<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

use App\Services\Contracts\CrudServiceInterface;

/**
 * Base CRUD service implementation.
 * Provides default CRUD operations using HandlesCrudOperations trait.
 */
abstract class BaseCrudService extends BaseService implements CrudServiceInterface {
    /**
     * Model class name for this service.
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * Create a new model instance.
     *
     * @param  Request|FormRequest  $request
     * @return Model|null
     */
    public function create(Request|FormRequest $request): ?Model {
        return $this->createModel(
            $this->modelClass,
            $request,
            beforeCreate: fn($data) => $this->beforeCreate($data, $request),
            afterCreate: fn($model) => $this->afterCreate($model, $request)
        );
    }

    /**
     * Update an existing model instance.
     *
     * @param  Model  $model
     * @param  Request|FormRequest  $request
     * @return bool
     */
    public function update(Model $model, Request|FormRequest $request): bool {
        return $this->updateModel(
            $model,
            $request,
            beforeUpdate: fn($data, $model) => $this->beforeUpdate($data, $model, $request),
            afterUpdate: fn($model) => $this->afterUpdate($model, $request)
        );
    }

    /**
     * Delete a model instance.
     *
     * @param  Model  $model
     * @return bool
     */
    public function delete(Model $model): bool {
        return $this->deleteModel(
            $model,
            beforeDelete: fn($model) => $this->beforeDelete($model),
            afterDelete: fn($model) => $this->afterDelete($model)
        );
    }

    /**
     * Hook called before creating a model.
     * Override this method to add custom logic.
     *
     * @param  array  $data
     * @param  Request|FormRequest  $request
     * @return array
     */
    protected function beforeCreate(array $data, Request|FormRequest $request): array {
        return $data;
    }

    /**
     * Hook called after creating a model.
     * Override this method to add custom logic.
     *
     * @param  Model  $model
     * @param  Request|FormRequest  $request
     * @return void
     */
    protected function afterCreate(Model $model, Request|FormRequest $request): void {
        $this->logInfo('Model created successfully', [
            'model_class' => get_class($model),
            'model_id'    => $model->id,
        ]);
    }

    /**
     * Hook called before updating a model.
     * Override this method to add custom logic.
     *
     * @param  array  $data
     * @param  Model  $model
     * @param  Request|FormRequest  $request
     * @return array
     */
    protected function beforeUpdate(array $data, Model $model, Request|FormRequest $request): array {
        return $data;
    }

    /**
     * Hook called after updating a model.
     * Override this method to add custom logic.
     *
     * @param  Model  $model
     * @param  Request|FormRequest  $request
     * @return void
     */
    protected function afterUpdate(Model $model, Request|FormRequest $request): void {
        $this->logInfo('Model updated successfully', [
            'model_class' => get_class($model),
            'model_id'    => $model->id,
        ]);
    }

    /**
     * Hook called before deleting a model.
     * Override this method to add custom logic.
     *
     * @param  Model  $model
     * @return void
     */
    protected function beforeDelete(Model $model): void {
        // Override if needed
    }

    /**
     * Hook called after deleting a model.
     * Override this method to add custom logic.
     *
     * @param  Model  $model
     * @return void
     */
    protected function afterDelete(Model $model): void {
        $this->logInfo('Model deleted successfully', [
            'model_class' => get_class($model),
            'model_id'    => $model->id,
        ]);
    }
}
