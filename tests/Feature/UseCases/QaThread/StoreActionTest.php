<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\User;
use App\UseCases\QaThread\StoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $validated = [
            'certification_id' => $certification->id,
            'title' => 'Laravelの認証について',
            'body' => 'Laravelの認証機能について質問があります。',
        ];

        $thread = app(StoreAction::class)($student, $validated);

        $thread->refresh();

        $this->assertSame($student->id, $thread->user_id);
        $this->assertSame($certification->id, $thread->certification_id);
        $this->assertSame($validated['title'], $thread->title);
        $this->assertSame($validated['body'], $thread->body);
        $this->assertSame(QaThreadStatus::Open, $thread->status);
        $this->assertNull($thread->resolved_at);
    }
}
