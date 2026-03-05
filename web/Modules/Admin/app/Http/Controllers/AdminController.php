<?php

namespace Modules\Admin\Http\Controllers;

class AdminController extends BaseAdminController {
    /**
     * Display a listing of the resource.
     * Route name: admin
     * Url: /admin
     */
    public function dashboard() {
        return view('admin::index');
    }
}
