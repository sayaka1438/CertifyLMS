<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\MeetingReminderWindow;
use App\Models\Meeting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingReminderNotification extends Notification
{
    public function __construct(
        public readonly Meeting $meeting,
        public readonly MeetingReminderWindow $window,
    ) {
    }

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
        $certificationName = $this->meeting->enrollment->certification->name;
        $topic = $this->meeting->topic;

        $mail = (new MailMessage)
            ->subject('【Certify LMS】' . $this->title())
            ->greeting('お世話になっております。')
            ->line("日時：{$scheduledAt}")
            ->line("資格：{$certificationName}")
            ->line("相談内容：{$topic}")
            ->action('面談を確認する', route('meetings.show', $this->meeting));

        return $mail
            ->salutation('Certify LMS 運営チーム');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'notification_type' => 'meeting_reminder_' . $this->window->value,
            'meeting_id' => $this->meeting->id,
            'url' => route('meetings.show', $this->meeting),
        ];
    }

    private function title(): string
    {
        return match ($this->window) {
            MeetingReminderWindow::Eve => '前日リマインド',
            MeetingReminderWindow::OneHourBefore => '1時間前リマインド',
        };
    }

    private function message(): string
    {
        $scheduledAt = $this->meeting->scheduled_at->format('Y年m月d日 H:i');

        return "{$scheduledAt}から面談が予定されています。";
    }
}
