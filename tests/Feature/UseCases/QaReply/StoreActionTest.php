<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaReply;

use App\Models\QaThread;
use App\Models\User;
use App\Notifications\QaReplyReceivedNotification;
use App\UseCases\QaReply\StoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StoreActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_reply_for_thread(): void
    {
        $thread = QaThread::factory()->create();

        $student = User::factory()->student()->inProgress()->create();

        $validated = [
            'body' => 'Laravelの認証機能についての回答です。',
        ];

        $result = app(StoreAction::class)($thread, $student, $validated);

        $this->assertSame($thread->id, $result->qa_thread_id);
        $this->assertSame($student->id, $result->user_id);
        $this->assertSame($validated['body'], $result->body);
    }

    public function test_sends_notification_to_thread_author(): void
    {
        Notification::fake();

        $author = User::factory()->student()->inProgress()->create();

        $thread = QaThread::factory()->create([
            'user_id' => $author->id,
        ]);

        $replyUser = User::factory()->coach()->create();

        $validated = [
            'body' => 'Laravelの認証機能についての回答です。',
        ];

        app(StoreAction::class)($thread, $replyUser, $validated);

        Notification::assertSentTo(
            $author,
            QaReplyReceivedNotification::class,
        );
    }

    public function test_does_not_send_notification_to_ineligible_thread_author(): void
    {
        Notification::fake();

        $author = User::factory()->student()->graduated()->create();

        $thread = QaThread::factory()->create([
            'user_id' => $author->id,
        ]);

        $replyUser = User::factory()->coach()->create();

        $validated = [
            'body' => 'Laravelの認証機能についての回答です。',
        ];

        app(StoreAction::class)($thread, $replyUser, $validated);

        Notification::assertNotSentTo(
            $author,
            QaReplyReceivedNotification::class,
        );
    }
}
