<?php

namespace Modules\Admin\Http\Controllers;

use Modules\Admin\Http\Requests\PersonalAccessTokenRequest;
use Modules\Admin\Models\PersonalAccessToken;

/**
 * Controller for managing PersonalAccessToken in Admin module.
 */
class PersonalAccessTokenController extends BaseAdminController {
    /**
     * Create a new controller instance.
     */
    public function __construct() {
        $this->modelClass   = PersonalAccessToken::class;
        $this->requestClass = PersonalAccessTokenRequest::class;
    }
}
