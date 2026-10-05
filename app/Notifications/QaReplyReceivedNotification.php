<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\QaThread;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QaReplyReceivedNotification extends Notification
{
    public function __construct(
        public readonly QaThread $thread
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
            ->subject('Q&Aに回答が届きました')
            ->greeting('Certify LMS をご利用の皆様へ')
            ->line("「{$this->thread->title}」に回答が投稿されました。")
            ->action('回答を確認する', route('qa-board.show', $this->thread))
            ->salutation('Certify LMS 運営チーム');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Q&Aに回答が届きました',
            'message' => "「{$this->thread->title}」に回答が投稿されました。",
            'notification_type' => 'qa_reply_received',
            'url' => route('qa-board.show', $this->thread),
        ];
    }
}
