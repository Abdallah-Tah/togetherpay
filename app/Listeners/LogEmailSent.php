<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class LogEmailSent
{
    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        $message = $event->sent->getSymfonySentMessage()->getOriginalMessage();

        // Get recipients
        $to = collect($message->getTo())->map(fn($addr) => $addr->getAddress())->implode(', ');
        $subject = $message->getSubject();

        // Get notification type if available
        $notificationType = 'Direct Email';
        if (isset($event->data['__laravel_notification'])) {
            $notificationType = class_basename($event->data['__laravel_notification']);
        }

        // Log successful email send
        Log::channel('mail')->info('📧 Email sent successfully', [
            'to' => $to,
            'subject' => $subject,
            'notification_type' => $notificationType,
            'timestamp' => now()->toDateTimeString(),
            'message_id' => $message->getHeaders()->get('Message-ID')?->getBodyAsString() ?? 'N/A',
        ]);
    }
}
