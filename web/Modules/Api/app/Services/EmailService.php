<?php

namespace Modules\Api\Services;

use Exception;
use Illuminate\Support\Facades\View;

use App\Utils\MailHandler;
use Modules\Logging\Utils\LogHandler;

/**
 * Service for sending emails.
 * Simple and flexible - just pass HTML content and subject.
 */
class EmailService {
    /**
     * Send email with HTML content.
     *
     * @param  string  $email
     * @param  string  $subject
     * @param  string  $htmlContent
     * @return bool
     */
    public static function sendEmail(string $email, string $subject, string $htmlContent): bool {
        try {
            return MailHandler::sendEmail($email, $subject, $htmlContent);
        } catch (Exception $e) {
            LogHandler::error('Failed to send email', [
                'email'   => $email,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return false;
        }
    }

    /**
     * Send email from Blade view template.
     *
     * @param  string  $email
     * @param  string  $subject
     * @param  string  $viewName  View name (e.g., 'api::email.register-otp')
     * @param  array  $data  Data to pass to view
     * @return bool
     */
    public static function sendEmailFromView(string $email, string $subject, string $viewName, array $data = []): bool {
        try {
            $htmlContent = View::make($viewName, $data)->render();

            return self::sendEmail($email, $subject, $htmlContent);
        } catch (Exception $e) {
            LogHandler::error('Failed to render email view', [
                'email'   => $email,
                'subject' => $subject,
                'view'    => $viewName,
                'error'   => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return false;
        }
    }
}
