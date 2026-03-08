<?php

namespace Modules\XSKT\Http\Controllers;

use Modules\XSKT\Http\Requests\DrawRequest;
use Modules\XSKT\Models\Draw;

/**
 * Controller for managing lottery draws.
 */
class DrawController extends BaseXsktController {
    public function __construct() {
        // Model class name
        $this->modelClass = Draw::class;
        // Request class name
        $this->requestClass = DrawRequest::class;
    }
}
