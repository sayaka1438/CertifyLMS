<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QaThreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_relation_returns_author_user(): void
    {
        $student = User::factory()->student()->create();
        $thread = QaThread::factory()->for($student)->create();

        $author = $thread->user;

        $this->assertTrue($author->is($student));
    }

    public function test_certification_relation_returns_target_certification(): void
    {
        $certification = Certification::factory()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $relatedCertification = $thread->certification;

        $this->assertTrue($relatedCertification->is($certification));
    }

    public function test_replies_relation_returns_attached_replies(): void
    {
        $thread = QaThread::factory()->create();

        QaReply::factory()->count(2)->for($thread, 'thread')->create();

        QaReply::factory()->create();

        $replies = $thread->replies;

        $this->assertCount(2, $replies);
    }

    public function test_status_cast_converts_to_enum(): void
    {
        $thread = QaThread::factory()->resolved()->create();

        $fresh = $thread->fresh();

        $this->assertInstanceOf(QaThreadStatus::class, $fresh->status);
        $this->assertSame(QaThreadStatus::Resolved, $fresh->status);
    }

    public function test_resolved_at_cast_returns_carbon(): void
    {
        $thread = QaThread::factory()->create([
            'resolved_at' => '2024-01-01 12:00:00',
        ]);

        $fresh = $thread->fresh();

        $this->assertInstanceOf(Carbon::class, $fresh->resolved_at);
        $this->assertSame('2024-01-01 12:00:00', $fresh->resolved_at->toDateTimeString());
    }
}
