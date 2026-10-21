<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\AiChat;

use App\Models\AiChatConversation;
use App\Models\User;
use App\UseCases\AiChat\IndexAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_users_conversations(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $conversations = AiChatConversation::factory()->count(2)->create([
            'user_id' => $student->id,
        ]);

        $otherConversation = AiChatConversation::factory()->create([
            'user_id' => $otherStudent->id,
        ]);

        $result = app(IndexAction::class)($student);

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($conversations[0]));
        $this->assertTrue($result->contains($conversations[1]));
        $this->assertFalse($result->contains($otherConversation));
    }

    public function test_returns_conversations_ordered_by_last_message_at_desc(): void
    {
        $student = User::factory()->student()->create();

        $middleConversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'last_message_at' => '2026-10-20 11:00:00',
        ]);

        $latestConversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'last_message_at' => '2026-10-20 12:00:00',
        ]);

        $oldConversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'last_message_at' => '2026-10-20 10:00:00',
        ]);

        $result = app(IndexAction::class)($student);

        $this->assertCount(3, $result);
        $this->assertTrue($result[0]->is($latestConversation));
        $this->assertTrue($result[1]->is($middleConversation));
        $this->assertTrue($result[2]->is($oldConversation));
    }

    public function test_returns_at_most_30_conversations(): void
    {
        $student = User::factory()->student()->create();

        AiChatConversation::factory()->count(31)->create([
            'user_id' => $student->id,
        ]);

        $result = app(IndexAction::class)($student);

        $this->assertCount(30, $result);
    }
}
