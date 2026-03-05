<?php

namespace Modules\Api\Http\Requests;

use App\Http\Requests\BaseRequest;

/**
 * Base request with common authorize logic per CRUD action.
 * Extends the shared BaseRequest to maintain backward compatibility.
 */
abstract class BaseApiRequest extends BaseRequest {
    // Base request for Api module
}
