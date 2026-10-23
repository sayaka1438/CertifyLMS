<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\QaThread;
use App\Models\User;
use App\Notifications\QaReplyReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaReplyReceivedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivers_via_database_and_mail(): void
    {
        $thread = QaThread::factory()->create();
        $student = User::factory()->student()->create();
        $replyUser = User::factory()->coach()->create();

        $notification = new QaReplyReceivedNotification($thread, $replyUser);

        $this->assertSame(
            ['database', 'mail'],
            $notification->via($student),
        );
    }

    public function test_returns_notification_data(): void
    {
        $thread = QaThread::factory()->create();
        $student = User::factory()->student()->create();
        $replyUser = User::factory()->coach()->create();

        $notification = new QaReplyReceivedNotification($thread, $replyUser);

        $data = $notification->toArray($student);

        $this->assertSame(
            [
                'title' => "{$replyUser->name}さんからQ&Aに回答が届きました",
                'message' => "「{$thread->title}」に回答が投稿されました。",
                'notification_type' => 'qa_reply_received',
                'url' => route('qa-board.show', $thread),
            ],
            $data,
        );
    }

    public function test_returns_mail_message(): void
    {
        $thread = QaThread::factory()->create();
        $student = User::factory()->student()->create();
        $replyUser = User::factory()->coach()->create();

        $notification = new QaReplyReceivedNotification($thread, $replyUser);

        $mail = $notification->toMail($student);

        $this->assertSame(
            "【Certify LMS】{$replyUser->name}さんからQ&Aに回答が届きました",
            $mail->subject,
        );

        $this->assertSame(
            'お世話になっております。',
            $mail->greeting,
        );

        $this->assertSame(
            '回答を確認する',
            $mail->actionText,
        );

        $this->assertSame(
            route('qa-board.show', $thread),
            $mail->actionUrl,
        );

        $this->assertSame(
            'Certify LMS 運営チーム',
            $mail->salutation,
        );
    }

    public function test_is_queued_with_retry_settings(): void
    {
        $thread = QaThread::factory()->create();
        $replyUser = User::factory()->coach()->create();

        $notification = new QaReplyReceivedNotification($thread, $replyUser);

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertSame(3, $notification->tries);
        $this->assertSame([10, 30], $notification->backoff);
    }
}
