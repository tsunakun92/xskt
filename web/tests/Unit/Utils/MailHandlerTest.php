<?php

namespace Tests\Unit\Utils;

use Exception;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Mail\SendEmail;
use App\Utils\MailHandler;

class MailHandlerTest extends TestCase {
    #[Test]
    public function it_sends_email_successfully_and_logs_info(): void {
        Mail::fake();

        $result = MailHandler::sendEmail('test@example.com', 'Hello', '<p>Body</p>');

        $this->assertTrue($result);

        Mail::assertSent(SendEmail::class, function (SendEmail $mail) {
            return $mail->data['subject'] === 'Hello'
                && $mail->data['content'] === '<p>Body</p>';
        });
    }

    #[Test]
    public function it_handles_exceptions_and_returns_false(): void {
        Mail::shouldReceive('to->send')
            ->once()
            ->andThrow(new Exception('SMTP error'));

        $result = MailHandler::sendEmail('fail@example.com', 'Fail', '<p>Body</p>');

        $this->assertFalse($result);
    }
}
