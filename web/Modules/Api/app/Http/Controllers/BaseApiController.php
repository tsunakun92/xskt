<?php

namespace Modules\Api\Http\Controllers;

use App\Http\Controllers\BaseController;

/**
 * Base controller for Api module.
 * Extends the shared BaseController to maintain backward compatibility.
 * All CRUD methods are inherited from App\Http\Controllers\BaseController.
 */
class BaseApiController extends BaseController {
    /**
     * Module name.
     *
     * @var string
     */
    protected string $moduleName = 'api';
}
