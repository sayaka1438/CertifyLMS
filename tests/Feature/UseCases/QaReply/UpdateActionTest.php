<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaReply;

use App\Models\QaReply;
use App\UseCases\QaReply\UpdateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_reply_body(): void
    {
        $reply = QaReply::factory()->create([
            'body' => '更新前の回答です。',
        ]);

        $validated = [
            'body' => '更新後の回答です。',
        ];

        $result = app(UpdateAction::class)($reply, $validated);

        $this->assertSame($validated['body'], $result->body);
    }
}
