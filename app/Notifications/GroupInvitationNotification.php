<?php

namespace App\Notifications;

use App\Mail\GroupInvitationMail;
use App\Models\GroupInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GroupInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public GroupInvitation $invitation
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): GroupInvitationMail
    {
        return new GroupInvitationMail($this->invitation);
    }
}
