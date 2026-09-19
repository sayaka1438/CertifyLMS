<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaReply;

use App\Models\QaReply;
use App\UseCases\QaReply\DestroyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_reply(): void
    {
        $reply = QaReply::factory()->create();

        app(DestroyAction::class)($reply);

        $this->assertDatabaseMissing('qa_replies', [
            'id' => $reply->id,
        ]);
    }
}
