<?php

namespace Tests\Unit\Mail;

use Illuminate\Mail\Mailable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Mail\SendEmail;

class SendEmailTest extends TestCase {
    #[Test]
    public function it_builds_mailable_with_subject_and_html_content(): void {
        $data = [
            'subject' => 'Test Subject',
            'content' => '<p>Test Content</p>',
        ];

        $mailable = new SendEmail($data);
        $this->assertInstanceOf(Mailable::class, $mailable);

        $built = $mailable->build();
        $this->assertInstanceOf(SendEmail::class, $built);
    }
}
