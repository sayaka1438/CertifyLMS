<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingReservedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var int
     */
    public $tries = 3;

    /**
     * @var array<int, int>
     */
    public $backoff = [10, 30];

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
            ->subject("【Certify LMS】{$this->meeting->student->name}さんから面談が予約されました")
            ->greeting('お世話になっております。')
            ->line("{$scheduledAt}の面談が予約されました。")
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
            'title' => "{$this->meeting->student->name}さんから面談が予約されました",
            'message' => "{$scheduledAt}の面談が予約されました。",
            'notification_type' => 'meeting_reserved',
            'url' => route('meetings.show', $this->meeting),
        ];
    }
}
