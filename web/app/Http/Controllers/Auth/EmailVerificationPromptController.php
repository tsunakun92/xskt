<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Http\Controllers\Controller;

class EmailVerificationPromptController extends Controller {
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View {
        return $request->user()->hasVerifiedEmail()
        ? redirect()->intended(route('admin', absolute : false))
        : view('auth.verify-email');
    }
}
