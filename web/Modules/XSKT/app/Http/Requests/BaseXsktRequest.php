<?php

namespace Modules\XSKT\Http\Requests;

use App\Http\Requests\BaseRequest;

/**
 * Base request with common authorize logic per CRUD action.
 * Extends the shared BaseRequest directly to avoid inter-module dependencies.
 */
abstract class BaseXsktRequest extends BaseRequest {
    // Base request for XSKT module
}
