<?php

namespace Modules\Admin\Http\Requests;

use App\Http\Requests\BaseRequest;

/**
 * Base request with common authorize logic per CRUD action.
 * Extends the shared BaseRequest to maintain backward compatibility.
 */
abstract class BaseAdminRequest extends BaseRequest {
    // Base request for Admin module
}
