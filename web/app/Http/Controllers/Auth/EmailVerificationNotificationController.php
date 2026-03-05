<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class EmailVerificationNotificationController extends Controller {
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('admin', absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
