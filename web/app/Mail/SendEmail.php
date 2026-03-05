<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Basic email mailable for HTML content.
 */
class SendEmail extends Mailable {
    use Queueable, SerializesModels;

    /**
     * Payload.
     *
     * @var array{subject: string, content: string}
     */
    public array $data;

    /**
     * Create a new message instance.
     *
     * @param  array{subject: string, content: string}  $data
     * @return void
     */
    public function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        return $this->subject($this->data['subject'])
            ->html($this->data['content']);
    }
}
