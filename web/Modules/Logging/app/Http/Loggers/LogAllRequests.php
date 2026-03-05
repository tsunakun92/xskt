<?php

namespace Modules\Logging\Http\Loggers;

use Illuminate\Http\Request;
use Spatie\HttpLogger\LogProfile;

class LogAllRequests implements LogProfile {
    /**
     * Log all requests (including GET)
     *
     * @param  Request  $request
     * @return bool
     */
    public function shouldLogRequest(Request $request): bool {
        // Don't log requests to logging dashboard to avoid infinite loop
        $path = $request->getPathInfo();
        if (str_starts_with($path, '/logs')) {
            return false;
        }

        return true;
    }
}
