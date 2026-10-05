<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingCanceledNotification extends Notification
{
    public function __construct(public readonly Meeting $meeting) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $scheduledAt = $this->meeting->scheduled_at->format('Y年m月d日 H:i');

        return (new MailMessage)
            ->subject('面談がキャンセルされました。')
            ->greeting('Certify LMS をご利用の皆様へ')
            ->line("{$scheduledAt}の面談がキャンセルされました。")
            ->action('面談を確認する', route('meetings.show', $this->meeting))
            ->salutation('Certify LMS 運営チーム');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $scheduledAt = $this->meeting->scheduled_at->format('Y年m月d日 H:i');

        return [
            'title' => '面談がキャンセルされました。',
            'message' => "{$scheduledAt}の面談がキャンセルされました。",
            'notification_type' => 'meeting_canceled',
            'url' => route('meetings.show', $this->meeting),
        ];
    }
}
