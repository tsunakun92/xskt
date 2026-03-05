<?php

namespace Modules\Admin\Http\Controllers;

use Modules\Admin\Http\Requests\PermissionRequest;
use Modules\Admin\Models\Permission;

class PermissionController extends BaseAdminController {
    public function __construct() {
        // Model class name
        $this->modelClass = Permission::class;
        // Request class name
        $this->requestClass = PermissionRequest::class;
    }
}
