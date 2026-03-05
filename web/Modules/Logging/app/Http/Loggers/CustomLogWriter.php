<?php

namespace Modules\Logging\Http\Loggers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\HttpLogger\LogWriter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CustomLogWriter implements LogWriter {
    /**
     * Log request with user information
     *
     * @param  Request  $request
     */
    public function logRequest(Request $request) {
        $message = $this->formatMessage($this->getMessage($request));

        Log::channel(config('http-logger.log_channel'))->log(config('http-logger.log_level', 'info'), $message);
    }

    /**
     * Get message data including user information
     *
     * @param  Request  $request
     * @return array
     */
    public function getMessage(Request $request) {
        $files = (new Collection(iterator_to_array($request->files)))
            ->map([$this, 'flatFiles'])
            ->flatten();

        // Get user information
        $user     = Auth::user();
        $userInfo = null;
        if ($user) {
            $userInfo = [
                'id'       => $user->id,
                'username' => $user->username ?? $user->name ?? $user->email ?? 'N/A',
                'email'    => $user->email ?? null,
            ];
        }

        return [
            'method'  => strtoupper($request->getMethod()),
            'uri'     => $request->getPathInfo(),
            'body'    => $request->except(config('http-logger.except', [])),
            'headers' => $request->headers->all(),
            'files'   => $files,
            'user'    => $userInfo,
            'ip'      => $request->ip(),
        ];
    }

    /**
     * Format message for logging
     *
     * @param  array  $message
     * @return string
     */
    protected function formatMessage(array $message) {
        $bodyAsJson    = json_encode($message['body']);
        $headersAsJson = json_encode($message['headers']);
        $files         = $message['files']->implode(',');

        // Format user information
        $userInfo = 'Guest';
        if ($message['user']) {
            $userInfo = $message['user']['username'] . ' (ID: ' . $message['user']['id'] . ')';
        }

        return "{$message['method']} {$message['uri']} - User: {$userInfo} - IP: {$message['ip']} - Body: {$bodyAsJson} - Headers: {$headersAsJson} - Files: " . $files;
    }

    /**
     * Flatten files array
     *
     * @param  mixed  $file
     * @return mixed
     */
    public function flatFiles($file) {
        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalName();
        }
        if (is_array($file)) {
            return array_map([$this, 'flatFiles'], $file);
        }

        return (string) $file;
    }
}
