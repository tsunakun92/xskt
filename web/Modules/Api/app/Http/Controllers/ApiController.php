<?php

namespace Modules\Api\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Api Controller
 * Basic controller structure for future use
 */
class ApiController extends BaseApiController {
    /**
     * Display a listing of the resource.
     * Route name: api
     * Url: /api
     */
    public function index(): View {
        return view('api::index');
    }
}
