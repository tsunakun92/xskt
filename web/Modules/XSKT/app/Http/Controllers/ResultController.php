<?php

namespace Modules\XSKT\Http\Controllers;

use Modules\XSKT\Http\Requests\ResultRequest;
use Modules\XSKT\Models\Result;

/**
 * Controller for managing lottery results.
 */
class ResultController extends BaseXsktController {
    public function __construct() {
        // Model class name
        $this->modelClass = Result::class;
        // Request class name
        $this->requestClass = ResultRequest::class;
    }
}
