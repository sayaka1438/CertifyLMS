<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AiChatMessageRole;
use App\Enums\AiChatMessageStatus;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiChatMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_relation_returns_parent_conversation(): void
    {
        $conversation = AiChatConversation::factory()->create();
        $message = AiChatMessage::factory()->create([
            'ai_chat_conversation_id' => $conversation->id,
        ]);

        $relatedConversation = $message->conversation;

        $this->assertTrue($relatedConversation->is($conversation));
    }

    public function test_role_is_cast_to_ai_chat_message_role_enum(): void
    {
        $message = AiChatMessage::factory()->create([
            'role' => AiChatMessageRole::User->value,
        ]);

        $this->assertInstanceOf(AiChatMessageRole::class, $message->role);
        $this->assertSame(AiChatMessageRole::User, $message->role);
    }

    public function test_status_is_cast_to_ai_chat_message_status_enum(): void
    {
        $message = AiChatMessage::factory()->create([
            'status' => AiChatMessageStatus::Completed->value,
        ]);

        $this->assertInstanceOf(AiChatMessageStatus::class, $message->status);
        $this->assertSame(AiChatMessageStatus::Completed, $message->status);
    }

    public function test_creating_message_updates_conversation_last_message_at(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'last_message_at' => null,
        ]);

        $message = AiChatMessage::factory()->create([
            'ai_chat_conversation_id' => $conversation->id,
        ]);

        $conversation->refresh();

        $this->assertNotNull($conversation->last_message_at);
        $this->assertTrue(
            $conversation->last_message_at->equalTo($message->created_at),
        );
    }
}
