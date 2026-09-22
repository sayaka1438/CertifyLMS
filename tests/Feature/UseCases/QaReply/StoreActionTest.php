<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaReply;

use App\Models\QaThread;
use App\Models\User;
use App\UseCases\QaReply\StoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
