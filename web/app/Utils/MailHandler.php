<?php

namespace App\Utils;

use Exception;
use Illuminate\Support\Facades\Mail;

use App\Mail\SendEmail;
use Modules\Logging\Utils\LogHandler;

/**
 * Mail handler utility class for sending emails.
 */
class MailHandler {
    /**
     * Send email (HTML content).
     *
     * Note: In local environment, email sending is skipped to avoid failures.
     *
     * @param  string  $sendToEmail
     * @param  string  $subject
     * @param  string  $content
     * @return bool
     */
    public static function sendEmail(string $sendToEmail, string $subject, string $content): bool {
        try {
            Mail::to($sendToEmail)->send(new SendEmail([
                'subject' => $subject,
                'content' => $content,
            ]));

            LogHandler::info('Email sent successfully', [
                'to'      => $sendToEmail,
                'subject' => $subject,
                'mailer'  => config('mail.default', 'smtp'),
            ], LogHandler::CHANNEL_API);

            return true;
        } catch (Exception $e) {
            LogHandler::error('Failed to send email', [
                'to'      => $sendToEmail,
                'subject' => $subject,
                'mailer'  => config('mail.default', 'smtp'),
                'error'   => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return false;
        }
    }
}
