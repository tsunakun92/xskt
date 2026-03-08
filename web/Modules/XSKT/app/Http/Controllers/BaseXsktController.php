<?php

namespace Modules\XSKT\Http\Controllers;

use App\Http\Controllers\BaseController;

/**
 * Base controller for XSKT module.
 * Extends the shared BaseController directly to avoid inter-module dependencies.
 * All CRUD methods are inherited from App\Http\Controllers\BaseController.
 */
class BaseXsktController extends BaseController {
    /**
     * Module name.
     *
     * @var string
     */
    protected string $moduleName = 'xskt';
}
