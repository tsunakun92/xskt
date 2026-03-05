<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

use App\Utils\DomainConst;
use Modules\Admin\Http\Requests\UserPermissionRequest;
use Modules\Admin\Http\Requests\UserRequest;
use Modules\Admin\Http\Requests\UserSettingRequest;
use Modules\Admin\Models\Permission;
use Modules\Admin\Models\User;
use Modules\Admin\Services\Contracts\UserServiceInterface;
use Modules\Logging\Utils\LogHandler;

class UserController extends BaseAdminController {
    /**
     * Initialize controller with dependency injection.
     *
     * @param  UserServiceInterface  $userService
     */
    public function __construct(
        protected UserServiceInterface $userService
    ) {
        // Model class name
        $this->modelClass = User::class;
        // Request class name
        $this->requestClass = UserRequest::class;
    }

    /**
     * Ensure user model is accessible in Admin UI.
     *
     * @param  object  $model
     * @return void
     */
    protected function ensureModelAccessible(object $model): void {
        if ($model instanceof User && $model->isSuperAdmin()) {
            abort(404);
        }
    }

    /**
     * Get extra actions for datatables
     *
     * @return array
     */
    protected function getExtraActions(): array {
        return [
            [
                'route'     => 'users.permission',
                'label'     => __('admin::crud.users.permission'),
                'iconClass' => 'fa-solid fa-key',
            ],
            [
                'route'     => 'users.setting',
                'label'     => __('admin::crud.users.setting'),
                'iconClass' => 'fa-solid fa-cog',
            ],
        ];
    }

    /**
     * Show permission of the specified resource.
     * Route name: users.permission
     * Url: /admin/users/{id}/permission
     *
     * @param  int  $id
     * @return View|RedirectResponse
     */
    public function permission(int $id) {
        $model = $this->modelClass::findOrFail($id);
        $this->ensureModelAccessible($model);
        $this->generateBreadcrumb($this->getRouteName(), $this->getRouteName() . '.permission');

        // Validate permission access
        $validationError = $model->validatePermissionAccess();
        if ($validationError) {
            return redirect()->route($this->getRouteName() . '.index')
                ->with('error', $validationError['message']);
        }

        // Get role permission keys to filter permissions
        $rolePermissionKeys = $model->getRolePermissionKeys();

        return view("{$this->moduleName}::users.permission", [
            'model'              => $model,
            'route'              => $this->getRouteName(),
            'groupedPermissions' => Permission::groupByModuleGroupFiltered($rolePermissionKeys),
            'currentPermissions' => $model->getCurrentPermissions(),
            'modules'            => Permission::getModulesArrayFiltered($rolePermissionKeys, false),
        ]);
    }

    /**
     * Update user permissions
     *
     * @param  int  $id
     * @param  UserPermissionRequest  $request
     * @return RedirectResponse
     */
    public function updatePermission(int $id, UserPermissionRequest $request): RedirectResponse {
        $model          = $this->modelClass::findOrFail($id);
        $this->ensureModelAccessible($model);
        $permissionKeys = $request->permissions ?? [];

        $retVal = $this->userService->updatePermissions($model, $permissionKeys);

        if (!$retVal) {
            LogHandler::warning('User permission update failed', ['user_id' => $id]);
        }

        return $this->redirectBackWithFlash($retVal, DomainConst::ACTION_PERMISSION, $this->getRouteName());
    }

    /**
     * Show settings of the specified resource.
     * Route name: users.setting
     * Url: /admin/users/{id}/setting
     *
     * @param  int  $id
     * @return View
     */
    public function setting(int $id): View {
        $model = $this->modelClass::findOrFail($id);
        $this->ensureModelAccessible($model);
        $this->generateBreadcrumb($this->getRouteName(), $this->getRouteName() . '.setting');

        $userSettings = $model->getUserSettingsWithDefaults(true);

        return view("{$this->moduleName}::users.settings", [
            'model'        => $model,
            'route'        => $this->getRouteName(),
            'userSettings' => $userSettings,
        ]);
    }

    /**
     * Update user settings
     *
     * @param  int  $id
     * @param  UserSettingRequest  $request
     * @return RedirectResponse
     */
    public function updateSetting(int $id, UserSettingRequest $request): RedirectResponse {
        $model           = $this->modelClass::findOrFail($id);
        $this->ensureModelAccessible($model);
        $requestSettings = $request->settings ?? [];

        $retVal = $this->userService->updateSettings($model, $requestSettings);

        if (!$retVal) {
            LogHandler::warning('User settings update failed', ['user_id' => $id]);
        }

        return $this->redirectBackWithFlash($retVal, 'setting', $this->getRouteName());
    }
}
