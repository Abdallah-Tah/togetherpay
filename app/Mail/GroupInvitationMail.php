<?php

namespace App\Mail;

use App\Models\GroupInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GroupInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public $groupName;

    public $invitedByName;

    public $token;

    public $invitationUrl;

    public $expiresAt;

    public $recipientEmail;

    /**
     * Create a new message instance.
     */
    public function __construct(GroupInvitation $invitation)
    {
        // Load relationships and extract only the data we need
        // This prevents serialization issues with Eloquent models in queues
        $invitation->loadMissing(['group', 'invitedBy']);

        $this->groupName = $invitation->group?->name ?? 'the group';
        $this->invitedByName = $invitation->invitedBy?->name ?? 'Someone';
        $this->token = $invitation->token;
        $this->expiresAt = $invitation->expires_at;
        $this->recipientEmail = $invitation->email;

        // Build universal link for mobile app
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $this->invitationUrl = "{$frontendUrl}/accept-invitation/{$this->token}";
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->recipientEmail,
            subject: "You've been invited to join {$this->groupName} on TogetherPay",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.group-invitation',
            with: [
                'groupName' => $this->groupName,
                'invitedByName' => $this->invitedByName,
                'token' => $this->token,
                'invitationUrl' => $this->invitationUrl,
                'expiresAt' => $this->expiresAt,
            ],
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
