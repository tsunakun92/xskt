<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\BaseController;

/**
 * Base controller for Admin module.
 * Extends the shared BaseController to maintain backward compatibility.
 * All CRUD methods are inherited from App\Http\Controllers\BaseController.
 */
class BaseAdminController extends BaseController {
    /**
     * Module name.
     *
     * @var string
     */
    protected string $moduleName = 'admin';
}
