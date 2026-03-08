<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExistingMemberAddedToGroupNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Group $group,
        public User $addedBy
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appUrl = config('app.frontend_url', config('app.url'));

        return (new MailMessage)
            ->subject("You've been added to {$this->group->name} on TogetherPay")
            ->markdown('emails.existing-member-added-to-group', [
                'notifiable' => $notifiable,
                'group' => $this->group,
                'addedBy' => $this->addedBy,
                'appUrl' => $appUrl,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'existing_member_added_to_group',
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'added_by' => $this->addedBy->name,
        ];
    }
}
