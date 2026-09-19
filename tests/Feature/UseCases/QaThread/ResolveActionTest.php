<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\QaThread;
use App\UseCases\QaThread\ResolveAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_thread(): void
    {
        $thread = QaThread::factory()->create([
            'status' => QaThreadStatus::Open,
            'resolved_at' => null,
        ]);

        $result = app(ResolveAction::class)($thread);

        $this->assertSame(QaThreadStatus::Resolved, $result->status);
        $this->assertNotNull($result->resolved_at);
    }
}
