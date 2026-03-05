<?php

namespace Modules\Api\Http\Controllers;

use Modules\Api\Http\Requests\ApiRegRequestRequest;
use Modules\Api\Models\ApiRegRequest;

class ApiRegRequestController extends BaseApiController {
    public function __construct() {
        // Model class name
        $this->modelClass = ApiRegRequest::class;
        // Request class name
        $this->requestClass = ApiRegRequestRequest::class;
    }
}
