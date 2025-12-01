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

    public $group;
    public $invitedBy;
    public $token;
    public $invitationUrl;
    public $expiresAt;
    public $recipientEmail;

    /**
     * Create a new message instance.
     */
    public function __construct(GroupInvitation $invitation)
    {
        // Load relationships but extract only the data we need
        // This prevents serialization issues with Eloquent models in queues
        $invitation->loadMissing(['group', 'invitedBy']);

        $this->group = $invitation->group;
        $this->invitedBy = $invitation->invitedBy;
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
            subject: "You've been invited to join {$this->group->name} on TogetherPay",
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
                'group' => $this->group,
                'invitedBy' => $this->invitedBy,
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
