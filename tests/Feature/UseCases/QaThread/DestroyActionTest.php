<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Models\QaThread;
use App\UseCases\QaThread\DestroyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_thread(): void
    {
        $thread = QaThread::factory()->create();

        app(DestroyAction::class)($thread);

        $this->assertDatabaseMissing('qa_threads', [
            'id' => $thread->id,
        ]);
    }
}
