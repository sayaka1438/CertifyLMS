<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChatMessageReceivedNotification extends Notification implements ShouldQueue
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

    public function __construct(
        public readonly ChatMessage $message,
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
            ->subject("【Certify LMS】{$this->message->sender->name}さんから新しいチャットメッセージが届きました")
            ->greeting('お世話になっております。')
            ->line("{$this->message->sender->name}さんからメッセージが届きました。")
            ->line($this->message->body)
            ->action('チャットを確認する', route('chat.show', $this->message->chatRoom))
            ->salutation('Certify LMS 運営チーム');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "{$this->message->sender->name}さんから新しいチャットメッセージが届きました",
            'message' => "{$this->message->sender->name}さんから「{$this->message->body}」",
            'notification_type' => 'chat_message_received',
            'url' => route('chat.show', $this->message->chatRoom),
        ];
    }
}
