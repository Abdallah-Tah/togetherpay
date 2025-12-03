<?php

namespace App\Mail;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public $groupName;

    public $invitedByName;

    public $token;

    public $invitationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Group $group, User $invitedBy, string $token)
    {
        // Extract only the data we need to prevent serialization issues
        $this->groupName = $group->name;
        $this->invitedByName = $invitedBy->name;
        $this->token = $token;

        // Build universal link for mobile app
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $this->invitationUrl = "{$frontendUrl}/register?token={$this->token}";
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been invited to join {$this->groupName} on TogetherPay",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.member-invitation',
            with: [
                'groupName' => $this->groupName,
                'invitedByName' => $this->invitedByName,
                'token' => $this->token,
                'invitationUrl' => $this->invitationUrl,
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
