<?php

namespace Modules\Admin\Http\Controllers;

use Modules\Admin\Http\Requests\SettingRequest;
use Modules\Admin\Models\Setting;

class SettingController extends BaseAdminController {
    public function __construct() {
        // Model class name
        $this->modelClass = Setting::class;
        // Request class name
        $this->requestClass = SettingRequest::class;
    }
}
