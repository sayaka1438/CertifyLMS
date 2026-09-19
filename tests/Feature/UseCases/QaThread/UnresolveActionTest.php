<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\QaThread;
use App\UseCases\QaThread\UnresolveAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnresolveActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unresolves_thread(): void
    {
        $thread = QaThread::factory()->create([
            'status' => QaThreadStatus::Resolved,
            'resolved_at' => now(),
        ]);

        $result = app(UnresolveAction::class)($thread);

        $this->assertSame(QaThreadStatus::Open, $result->status);
        $this->assertNull($result->resolved_at);
    }
}
