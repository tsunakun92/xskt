<?php

namespace Modules\Api\Http\Controllers;

use Modules\Api\Http\Requests\ApiRequestLogRequest;
use Modules\Api\Models\ApiRequestLog;

/**
 * Controller for API request logs management.
 * Inherits full CRUD behavior from BaseApiController but effectively
 * used as read-only listing and detail view for logs.
 */
class ApiRequestLogController extends BaseApiController {
    /**
     * ApiRequestLogController constructor.
     */
    public function __construct() {
        // Model class name
        $this->modelClass = ApiRequestLog::class;
        // Request class name
        $this->requestClass = ApiRequestLogRequest::class;
    }
}
