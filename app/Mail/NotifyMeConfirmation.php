<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyMeConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public $recipient;

    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        Log::info('NotifyMeConfirmation: Mailable instance created');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('NotifyMeConfirmation: Email failed to send', [
            'recipient' => $this->recipient ?? 'unknown',
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        Log::info('NotifyMeConfirmation: Envelope called');

        return new Envelope(
            subject: "You're on the list! - TogetherPay",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        Log::info('NotifyMeConfirmation: Content called');

        return new Content(
            view: 'emails.notify-me-confirmation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
