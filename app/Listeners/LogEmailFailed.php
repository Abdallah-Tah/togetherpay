<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSendingFailed;
use Illuminate\Support\Facades\Log;

class LogEmailFailed
{
    /**
     * Handle the event.
     */
    public function handle(MessageSendingFailed $event): void
    {
        $message = $event->message;

        // Get recipients
        $to = collect($message->getTo())->map(fn($addr) => $addr->getAddress())->implode(', ');
        $subject = $message->getSubject();

        // Log failed email send
        Log::channel('mail')->error('❌ Email failed to send', [
            'to' => $to,
            'subject' => $subject,
            'error' => $event->exception?->getMessage() ?? 'Unknown error',
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
