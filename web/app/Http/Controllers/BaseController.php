<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Services\Contracts\CrudServiceInterface;
use App\Utils\DomainConst;
use App\Utils\FileMasterHelper;
use App\Utils\SqlHandler;
use Modules\Admin\Models\FileMaster;
use Modules\Logging\Utils\LogHandler;

/**
 * Base controller for all modules.
 * Provides full CRUD operations, breadcrumb generation, permission checking,
 * file upload handling, and module-specific view routing.
 */
abstract class BaseController extends Controller {
    /**
     * Module name.
     *
     * @var string
     */
    protected string $moduleName = '';

    /**
     * Model class name.
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * Request class name for validation.
     *
     * @var string
     */
    protected string $requestClass;

    /**
     * Table name (optional, will be derived from model if not set).
     *
     * @var string|null
     */
    protected ?string $tableName = null;

    /**
     * Route name (optional, will be derived from table name if not set).
     *
     * @var string|null
     */
    protected ?string $routeName = null;

    /**
     * CRUD service instance (optional).
     * If set, CRUD operations will use this service instead of direct model operations.
     *
     * @var CrudServiceInterface|null
     */
    protected ?CrudServiceInterface $crudService = null;

    /**
     * Create controller instance.
     *
     * @param  Container  $container
     * @return void
     */
    public function __construct(
        protected Container $container
    ) {}

    /**
     * Ensure the given model can be accessed via the current controller.
     * Child controllers can override this to implement model-level access guards.
     *
     * @param  object  $model
     * @return void
     */
    protected function ensureModelAccessible(object $model): void {
        // Default implementation: no additional access restrictions.
    }

    /**
     * Get CRUD service instance.
     * Override this method in child controllers to provide a service.
     *
     * @return CrudServiceInterface|null
     */
    protected function getCrudService(): ?CrudServiceInterface {
        return $this->crudService;
    }

    /**
     * Get table name for the model.
     * Returns custom table name if set, otherwise derives from model.
     *
     * @return string Table name
     */
    protected function getTableName(): string {
        return $this->tableName ?? $this->modelClass::getDatatableTableName();
    }

    /**
     * Get route name for the model.
     * Returns custom route name if set, otherwise derives from table name.
     *
     * @return string Route name
     */
    protected function getRouteName(): string {
        if ($this->routeName === null) {
            $this->routeName = kebab_case($this->getTableName());
        }

        return $this->routeName;
    }

    /**
     * Generate default breadcrumb array for admin pages
     *
     * @param  string  $routeName  Current route name
     * @param  string  $action  Current action (list, create, edit, show)
     * @param  string|null  $title  Optional custom title
     * @param  array  $additionalItems  Additional breadcrumb items to append
     * @return void
     */
    protected function generateBreadcrumb(string $routeName, string $action, ?string $title = null, array $additionalItems = []): void {
        $defaultItems = [
            [
                'label' => $title ?? __("{$this->moduleName}::crud.{$routeName}.title"),
                'url'   => route("{$routeName}.index"),
                'icon'  => '',
            ],
            [
                'label' => __("{$this->moduleName}::crud.{$action}"),
                'url'   => '',
                'icon'  => '',
            ],
        ];
        $defaultItems = array_merge($defaultItems, $additionalItems);
        view()->share('breadcrumb', $defaultItems);
    }

    /**
     * Create custom breadcrumb with flexible structure
     *
     * @param  array  $items  Array of breadcrumb items, each item should have: label, url (optional), icon (optional)
     *
     * @example
     * $items = [
     *    ['label' => 'Home', 'url' => '/'],
     *    ['label' => 'Category', 'url' => '/categories'],
     *    ['label' => 'Product', 'url' => '/categories/products'],
     *    ['label' => 'Edit']  // Current page usually doesn't have URL
     * ];
     *
     * @return void
     */
    protected function generateCustomBreadcrumb(array $items): void {
        view()->share('breadcrumb', $items);
    }

    /**
     * Validate request.
     *
     * @param  Request|null  $request
     * @param  array  $rules
     * @return Request|FormRequest
     */
    protected function validateRequest(?Request $request = null, array $rules = []): Request|FormRequest {
        if ($request === null) {
            $requestClass = $this->requestClass ?? Request::class;
            $request      = $this->container->make($requestClass);
        }

        // If request is not a FormRequest, try to create one from requestClass
        if (!($request instanceof FormRequest) && $this->requestClass) {
            $formRequestClass = $this->requestClass;
            if (is_subclass_of($formRequestClass, FormRequest::class)) {
                $formRequest = app($formRequestClass);
                $formRequest->merge($request->all());
                $formRequest->setContainer(app());
                $formRequest->setRedirector(redirect());
                $formRequest->validateResolved();
                $request = $formRequest;
            }
        } elseif ($request instanceof FormRequest) {
            // validateResolved() will trigger validation and redirect if fails
            $request->validateResolved();
        } elseif ($rules) {
            $request->validate($rules);
        }

        return $request;
    }

    /**
     * Check permission for current user.
     *
     * @param  string  $permission
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     *
     * @return void
     */
    protected function checkPermission(string $permission): void {
        if (!can_access($permission)) {
            LogHandler::warning('Access denied - no permission', [
                'permission' => $permission,
                'route'      => request()->route()?->getName(),
                'url'        => request()->fullUrl(),
            ]);
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Get extra actions for datatables.
     *
     * @return array
     */
    protected function getExtraActions(): array {
        return [];
    }

    /**
     * Get extra filter form view name for datatables.
     * Child controllers can override this to provide custom filter form blade.
     *
     * @return string|null
     */
    protected function getExtraFilterForm(): ?string {
        return null;
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(): View {
        $action    = DomainConst::ACTION_INDEX;
        $routeName = $this->getRouteName();
        $this->generateBreadcrumb($routeName, $action);

        $viewName = "{$this->moduleName}::layouts.$action";
        if (view()->exists("{$this->moduleName}::{$routeName}.$action")) {
            $viewName = "{$this->moduleName}::{$routeName}.$action";
        }

        return view($viewName, [
            'data'  => [
                'modelClass'      => $this->modelClass,
                'routeName'       => $routeName,
                'extraActions'    => $this->getExtraActions(),
                'extraFilterForm' => $this->getExtraFilterForm(),
            ],
            'route' => $routeName,
        ]);
    }

    /**
     * Show the details of the specified resource.
     *
     * @param  int  $id
     * @return View
     */
    public function show(int $id): View {
        $action    = DomainConst::ACTION_SHOW;
        $routeName = $this->getRouteName();

        try {
            $model = $this->modelClass::findOrFail($id);
            $this->ensureModelAccessible($model);
            $this->generateBreadcrumb($routeName, $action);

            LogHandler::info('Viewing details', [
                'route_name' => $routeName,
                'action'     => $action,
                'model_id'   => $id,
                'model'      => $this->modelClass,
            ]);

            $viewName = "{$this->moduleName}::layouts.$action";
            if (view()->exists("{$this->moduleName}::{$routeName}.$action")) {
                $viewName = "{$this->moduleName}::{$routeName}.$action";
            }

            return view($viewName, [
                'model'  => $model,
                'route'  => $routeName,
                'fields' => $this->modelClass::getFormFields($routeName, $action),
            ]);
        } catch (Exception $e) {
            LogHandler::error('Error viewing details', [
                'route_name' => $routeName,
                'action'     => $action,
                'model_id'   => $id,
                'model'      => $this->modelClass,
            ], $e);

            throw $e;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create(): View {
        $action    = DomainConst::ACTION_CREATE;
        $routeName = $this->getRouteName();

        $this->generateBreadcrumb($routeName, $action);

        $viewName = "{$this->moduleName}::layouts.form";
        if (view()->exists("{$this->moduleName}::{$routeName}.form")) {
            $viewName = "{$this->moduleName}::{$routeName}.form";
        }

        return view($viewName, [
            'model'     => new $this->modelClass,
            'route'     => $routeName,
            'fields'    => $this->modelClass::getFormFields($routeName, $action),
            'action'    => $action,
            'actionUrl' => route("{$routeName}.store"),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return View
     */
    public function edit(int $id): View {
        $action    = DomainConst::ACTION_EDIT;
        $routeName = $this->getRouteName();

        try {
            $model = $this->modelClass::findOrFail($id);
            $this->ensureModelAccessible($model);
            $this->generateBreadcrumb($routeName, $action);

            $viewName = "{$this->moduleName}::layouts.form";
            if (view()->exists("{$this->moduleName}::{$routeName}.form")) {
                $viewName = "{$this->moduleName}::{$routeName}.form";
            }

            return view($viewName, [
                'model'     => $model,
                'route'     => $routeName,
                'fields'    => $this->modelClass::getFormFields($routeName, $action, $model),
                'action'    => $action,
                'actionUrl' => route("{$routeName}.update", $model),
            ]);
        } catch (Exception $e) {
            LogHandler::error('Error accessing edit form', [
                'route_name' => $routeName,
                'action'     => $action,
                'model_id'   => $id,
                'model'      => $this->modelClass,
            ], $e);

            throw $e;
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse {
        $routeName = $this->getRouteName();
        $action    = DomainConst::ACTION_CREATE;

        $this->checkPermission($routeName . '.' . $action);
        $request = $this->validateRequest($request);

        $service = $this->getCrudService();

        if ($service) {
            // Use service layer
            $retVal = SqlHandler::handleTransaction(function () use ($request, $service): bool {
                $created = $service->create($request);
                if (!$created) {
                    throw new Exception(__("{$this->moduleName}::crud.create_failed", ['value' => __("{$this->moduleName}::crud.{$this->getRouteName()}.title")]));
                }

                $this->handleFileUploads($request, $created->id);

                return true;
            });
        } else {
            // Fallback to direct model operations (backward compatibility)
            $retVal = SqlHandler::handleTransaction(function () use ($request, $routeName): bool {
                $data = ($request instanceof FormRequest)
                    ? $request->validated()
                    : $request->only((new $this->modelClass)->getFillable());

                $created = $this->modelClass::create($data);
                if (!$created) {
                    throw new Exception(__("{$this->moduleName}::crud.create_failed", ['value' => __("{$this->moduleName}::crud.{$routeName}.title")]));
                }

                $this->handleFileUploads($request, $created->id);

                return true;
            });
        }

        if (!$retVal) {
            LogHandler::warning('Create failed', [
                'route_name' => $routeName,
                'action'     => $action,
                'model'      => $this->modelClass,
            ]);
        }

        return $this->redirectWithFlash($retVal, $action, $routeName);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return RedirectResponse
     */
    public function update(Request $request, int $id): RedirectResponse {
        $routeName = $this->getRouteName();
        $action    = DomainConst::ACTION_EDIT;

        $this->checkPermission($routeName . '.' . $action);
        $request = $this->validateRequest($request);
        $model   = $this->modelClass::findOrFail($id);
        $this->ensureModelAccessible($model);

        $service = $this->getCrudService();

        if ($service) {
            // Use service layer
            $retVal = SqlHandler::handleTransaction(function () use ($request, $model, $service): bool {
                $updated = $service->update($model, $request);
                if (!$updated) {
                    throw new Exception(__("{$this->moduleName}::crud.update_failed", ['value' => __("{$this->moduleName}::crud.{$this->getRouteName()}.title")]));
                }

                $this->handleFileUploads($request, $model->id);
                $this->handleFileDeletions($request, $model->id);

                return true;
            });
        } else {
            // Fallback to direct model operations (backward compatibility)
            $retVal = SqlHandler::handleTransaction(function () use ($request, $model, $routeName) {
                $data = ($request instanceof FormRequest)
                    ? $request->validated()
                    : $request->only($model->getFillable());

                $updated = $model->update($data);
                if (!$updated) {
                    throw new Exception(__("{$this->moduleName}::crud.update_failed", ['value' => __("{$this->moduleName}::crud.{$routeName}.title")]));
                }

                $this->handleFileUploads($request, $model->id);
                $this->handleFileDeletions($request, $model->id);

                return true;
            });
        }

        if (!$retVal) {
            LogHandler::warning('Update failed', [
                'route_name' => $routeName,
                'action'     => $action,
                'model_id'   => $id,
                'model'      => $this->modelClass,
            ]);
        }

        return $this->redirectWithFlash($retVal, $action, $routeName);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse {
        $routeName = $this->getRouteName();
        $action    = DomainConst::ACTION_DESTROY;

        $this->checkPermission($routeName . '.' . $action);
        $model = $this->modelClass::findOrFail($id);
        $this->ensureModelAccessible($model);

        $service = $this->getCrudService();

        if ($service) {
            // Use service layer
            $retVal = SqlHandler::handleTransaction(function () use ($model, $service): bool {
                $deleted = $service->delete($model);
                if (!$deleted) {
                    throw new Exception(__("{$this->moduleName}::crud.delete_failed", ['value' => __("{$this->moduleName}::crud.{$this->getRouteName()}.title")]));
                }

                return true;
            });
        } else {
            // Fallback to direct model operations (backward compatibility)
            $retVal = SqlHandler::handleTransaction(function () use ($model, $routeName): bool {
                $deleted = $model->delete();
                if (!$deleted) {
                    throw new Exception(__("{$this->moduleName}::crud.delete_failed", ['value' => __("{$this->moduleName}::crud.{$routeName}.title")]));
                }

                return true;
            });
        }

        if (!$retVal) {
            LogHandler::warning('Delete failed', [
                'route_name' => $routeName,
                'action'     => $action,
                'model_id'   => $id,
                'model'      => $this->modelClass,
            ]);
        }

        return $this->redirectWithFlash($retVal, $action, $routeName);
    }

    /**
     * Get lang key with fallback (try {routeName}.{key} first, then {key})
     *
     * @param  string  $key
     * @param  string  $routeName
     * @param  array  $replace
     * @return string
     */
    protected function getLangKey(string $key, string $routeName, array $replace = []): string {
        $routeKey    = "{$this->moduleName}::crud.{$routeName}.{$key}";
        $fallbackKey = "{$this->moduleName}::crud.{$key}";

        // Try route-specific key first
        if (__($routeKey) !== $routeKey) {
            return __($routeKey, $replace);
        }

        // Fallback to general key
        return __($fallbackKey, $replace);
    }

    /**
     * Handle redirection with flash messages
     *
     * @param  bool  $success
     * @param  string  $action
     * @param  string  $routeName
     * @return RedirectResponse
     */
    protected function redirectWithFlash(bool $success, string $action, string $routeName): RedirectResponse {
        if ($success) {
            $message = $this->getLangKey($action . '_success', $routeName, ['value' => __("{$this->moduleName}::crud.{$routeName}.title")]);
            flash()->addSuccess($message);

            return redirect()->route("$routeName.index");
        }

        $message = $this->getLangKey($action . '_failed', $routeName, ['value' => session('transaction_error')]);
        flash()->addError($message);

        return redirect()->back();
    }

    /**
     * Handle file uploads for the model.
     * Moves files from temporary storage to permanent storage.
     *
     * @param  Request  $request
     * @param  int  $belongId
     * @return void
     */
    protected function handleFileUploads(Request $request, int $belongId): void {
        $belongType = $this->getBelongType();
        if (!$belongType) {
            LogHandler::debug('handleFileUploads: No belongType found', [
                'belong_id' => $belongId,
            ]);

            return;
        }

        // Get tmp filenames from request
        $tmpFiles = $request->input('tmp_files', '');

        LogHandler::debug('handleFileUploads called', [
            'belong_type'     => $belongType,
            'belong_id'       => $belongId,
            'tmp_files_input' => $tmpFiles,
        ]);

        if (empty($tmpFiles)) {
            LogHandler::debug('handleFileUploads: No tmp files in request');

            return;
        }

        // Parse comma-separated filenames
        $tmpFilenames = array_filter(array_map('trim', explode(',', $tmpFiles)));

        LogHandler::debug('handleFileUploads: Parsed tmp filenames', [
            'tmp_filenames' => $tmpFilenames,
            'count'         => count($tmpFilenames),
        ]);

        if (!empty($tmpFilenames)) {
            FileMasterHelper::moveMultipleFromTmp($tmpFilenames, $belongType, $belongId);
        }
    }

    /**
     * Handle file deletions for the model.
     *
     * @param  Request  $request
     * @param  int  $belongId
     * @return void
     */
    protected function handleFileDeletions(Request $request, int $belongId): void {
        $belongType = $this->getBelongType();
        if (!$belongType) {
            LogHandler::debug('handleFileDeletions: No belongType found', [
                'belong_id' => $belongId,
            ]);

            return;
        }

        // Get deleted file IDs from request (can be comma-separated string or array)
        $deletedFiles = $request->input('deleted_files', '');

        LogHandler::debug('handleFileDeletions called', [
            'belong_type'         => $belongType,
            'belong_id'           => $belongId,
            'deleted_files_input' => $deletedFiles,
            'all_input_keys'      => array_keys($request->all()),
        ]);

        if (empty($deletedFiles)) {
            LogHandler::debug('handleFileDeletions: No deleted files in request');

            return;
        }

        // Parse comma-separated IDs or use array directly
        if (is_string($deletedFiles)) {
            $deletedFileIds = array_filter(array_map('intval', explode(',', $deletedFiles)));
        } else {
            $deletedFileIds = array_filter(array_map('intval', (array) $deletedFiles));
        }

        LogHandler::debug('handleFileDeletions: Parsed deleted file IDs', [
            'deleted_file_ids' => $deletedFileIds,
        ]);

        if (!empty($deletedFileIds)) {
            $deleted = FileMaster::where('belong_type', $belongType)
                ->where('belong_id', $belongId)
                ->whereIn('id', $deletedFileIds)
                ->get();

            LogHandler::info('Deleting files', [
                'belong_type' => $belongType,
                'belong_id'   => $belongId,
                'file_ids'    => $deletedFileIds,
                'files_found' => $deleted->count(),
            ]);

            // Delete files from storage before deleting records
            foreach ($deleted as $file) {
                if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                    Storage::disk('public')->delete($file->file_path);
                    LogHandler::info('Deleted file from storage', [
                        'file_id'   => $file->id,
                        'file_path' => $file->file_path,
                    ]);
                }
            }

            // Delete records
            $deletedCount = FileMaster::where('belong_type', $belongType)
                ->where('belong_id', $belongId)
                ->whereIn('id', $deletedFileIds)
                ->delete();

            LogHandler::info('Deleted file records', [
                'deleted_count' => $deletedCount,
            ]);
        }
    }

    /**
     * Handle redirection back with flash messages.
     *
     * @param  bool  $success
     * @param  string  $action
     * @param  string  $routeName
     * @return RedirectResponse
     */
    protected function redirectBackWithFlash(bool $success, string $action, string $routeName): RedirectResponse {
        if ($success) {
            $message = $this->getLangKey($action . '_success', $routeName, ['value' => __("{$this->moduleName}::crud.{$routeName}.title")]);
            flash()->addSuccess($message);

            return redirect()->back()->withInput();
        }

        $message = $this->getLangKey($action . '_failed', $routeName, ['value' => session('transaction_error')]);
        flash()->addError($message);

        return redirect()->back()->withInput();
    }
}
