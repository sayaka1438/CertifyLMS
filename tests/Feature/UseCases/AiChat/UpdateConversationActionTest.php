<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\AiChat;

use App\Models\AiChatConversation;
use App\UseCases\AiChat\UpdateConversationAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateConversationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_title_and_disables_auto_title(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'title' => '新規相談',
            'auto_title_enabled' => true,
        ]);

        $result = app(UpdateConversationAction::class)(
            conversation: $conversation,
            title: '変更後のタイトル',
        );

        $this->assertSame('変更後のタイトル', $result->title);
        $this->assertFalse($result->auto_title_enabled);

        $this->assertDatabaseHas('ai_chat_conversations', [
            'id' => $conversation->id,
            'title' => '変更後のタイトル',
            'auto_title_enabled' => false,
        ]);
    }
}
