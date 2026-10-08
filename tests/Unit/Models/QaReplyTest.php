<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_thread_relation_returns_parent_thread(): void
    {
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->for($thread, 'thread')->create();

        $parent = $reply->thread;

        $this->assertTrue($parent->is($thread));
    }

    public function test_user_relation_returns_author_user(): void
    {
        $student = User::factory()->student()->create();
        $reply = QaReply::factory()->for($student)->create();

        $author = $reply->user;

        $this->assertTrue($author->is($student));
    }
}
