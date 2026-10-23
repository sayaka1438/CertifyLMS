<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAnnouncementNotification extends Notification
{
    public function __construct(
        public readonly Announcement $announcement,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("【Certify LMS】{$this->announcement->title}")
            ->greeting('管理者からのお知らせ')
            ->line($this->announcement->body)
            ->action('お知らせを確認する', route('notifications.index'))
            ->salutation('Certify LMS 運営チーム');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->announcement->title,
            'body' => $this->announcement->body,
            'message' => $this->announcement->body,
            'notification_type' => 'admin_announcement',
        ];
    }
}
