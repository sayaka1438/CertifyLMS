<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\ChatMessage;
use App\Models\User;
use App\Notifications\ChatMessageReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatMessageReceivedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivers_via_database_and_mail(): void
    {
        $message = ChatMessage::factory()->create();
        $student = User::factory()->student()->create();

        $notification = new ChatMessageReceivedNotification($message);

        $this->assertSame(
            ['database', 'mail'],
            $notification->via($student),
        );
    }

    public function test_returns_notification_data(): void
    {
        $sender = User::factory()->coach()->create();

        $message = ChatMessage::factory()->create([
            'sender_user_id' => $sender->id,
            'body' => 'チャットメッセージです。',
        ]);

        $student = User::factory()->student()->create();

        $notification = new ChatMessageReceivedNotification($message);

        $data = $notification->toArray($student);

        $this->assertSame(
            [
                'title' => '新しいチャットメッセージが届きました',
                'message' => "{$sender->name}さんから「チャットメッセージです。」",
                'notification_type' => 'chat_message_received',
                'url' => route('chat.show', $message->chatRoom),
            ],
            $data,
        );
    }

    public function test_returns_mail_message(): void
    {
        $sender = User::factory()->coach()->create();

        $message = ChatMessage::factory()->create([
            'sender_user_id' => $sender->id,
            'body' => 'チャットメッセージです。',
        ]);

        $student = User::factory()->student()->create();

        $notification = new ChatMessageReceivedNotification($message);

        $mail = $notification->toMail($student);

        $this->assertSame(
            '新しいチャットメッセージが届きました',
            $mail->subject,
        );

        $this->assertSame(
            'Certify LMS をご利用の皆様へ',
            $mail->greeting,
        );

        $this->assertSame(
            'チャットを確認する',
            $mail->actionText,
        );

        $this->assertSame(
            route('chat.show', $message->chatRoom),
            $mail->actionUrl,
        );

        $this->assertSame(
            'Certify LMS 運営チーム',
            $mail->salutation,
        );
    }
}
