<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\QaThread;
use App\Models\User;
use App\Notifications\QaReplyReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaReplyReceivedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivers_via_database_and_mail(): void
    {
        $thread = QaThread::factory()->create();
        $student = User::factory()->student()->create();

        $notification = new QaReplyReceivedNotification($thread);

        $this->assertSame(
            ['database', 'mail'],
            $notification->via($student),
        );
    }

    public function test_returns_notification_data(): void
    {
        $thread = QaThread::factory()->create();
        $student = User::factory()->student()->create();

        $notification = new QaReplyReceivedNotification($thread);

        $data = $notification->toArray($student);

        $this->assertSame(
            [
                'title' => 'Q&Aに回答が届きました',
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

        $notification = new QaReplyReceivedNotification($thread);

        $mail = $notification->toMail($student);

        $this->assertSame(
            'Q&Aに回答が届きました',
            $mail->subject,
        );

        $this->assertSame(
            'Certify LMS をご利用の皆様へ',
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
}
