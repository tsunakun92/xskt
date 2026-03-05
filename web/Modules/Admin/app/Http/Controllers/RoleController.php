<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

use App\Utils\DomainConst;
use Modules\Admin\Http\Requests\RolePermissionRequest;
use Modules\Admin\Http\Requests\RoleRequest;
use Modules\Admin\Models\Permission;
use Modules\Admin\Models\Role;
use Modules\Admin\Services\Contracts\RoleServiceInterface;
use Modules\Logging\Utils\LogHandler;

class RoleController extends BaseAdminController {
    /**
     * Initialize controller.
     *
     * @param  RoleServiceInterface  $roleService
     * @return void
     */
    public function __construct(
        protected RoleServiceInterface $roleService
    ) {
        // Model class name
        $this->modelClass = Role::class;
        // Request class name
        $this->requestClass = RoleRequest::class;
    }

    /**
     * Ensure role model is accessible in Admin UI.
     *
     * @param  object  $model
     * @return void
     */
    protected function ensureModelAccessible(object $model): void {
        if ($model instanceof Role && $model->isSuperAdmin()) {
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
                'route'     => 'roles.permission',
                'label'     => __('admin::crud.roles.permission'),
                'iconClass' => 'fa-solid fa-key',
            ],
        ];
    }

    /**
     * Show permission of the specified resource.
     * Route name: roles.permission
     * Url: /admin/roles/{id}/permission
     *
     * @param  int  $id
     * @return View
     */
    public function permission(int $id): View {
        $model = $this->modelClass::findOrFail($id);
        $this->ensureModelAccessible($model);
        $this->generateBreadcrumb($this->getRouteName(), $this->getRouteName() . '.permission');

        return view("{$this->moduleName}::roles.permission", [
            'model'              => $model,
            'route'              => $this->getRouteName(),
            'groupedPermissions' => Permission::groupByModuleGroup(),
            'currentPermissions' => $model->getCurrentPermissions(),
            'modules'            => Permission::getModulesArray(false),
        ]);
    }

    /**
     * Update role permissions
     *
     * @param  int  $id
     * @param  RolePermissionRequest  $request
     * @return RedirectResponse
     */
    public function updatePermission(int $id, RolePermissionRequest $request): RedirectResponse {
        $model          = $this->modelClass::findOrFail($id);
        $this->ensureModelAccessible($model);
        $permissionKeys = $request->permissions ?? [];

        $retVal = $this->roleService->updatePermissions($model, $permissionKeys);

        if (!$retVal) {
            LogHandler::warning('Permission update failed', ['role_id' => $id]);
        }

        return $this->redirectBackWithFlash($retVal, DomainConst::ACTION_PERMISSION, $this->getRouteName());
    }
}
