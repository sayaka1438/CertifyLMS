<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use App\UseCases\QaThread\ShowAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_loads_required_relations_for_thread_detail(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($student)
            ->for($certification)
            ->create();

        $replyUser = User::factory()->student()->inProgress()->create();

        QaReply::factory()
            ->for($thread, 'thread')
            ->for($replyUser)
            ->create();

        $result = app(ShowAction::class)($thread);

        $this->assertTrue($result->relationLoaded('user'));
        $this->assertTrue($result->relationLoaded('certification'));
        $this->assertTrue($result->relationLoaded('replies'));

        $resultReply = $result->replies->first();

        $this->assertTrue($resultReply->relationLoaded('user'));
    }
}
