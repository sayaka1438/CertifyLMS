<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\AiChat;

use App\Models\AiChatConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateConversationRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_data(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $conversation = AiChatConversation::factory()
            ->for($student)
            ->create();

        $data = [
            'title' => 'PHPについての質問',
        ];

        $response = $this->actingAs($student)->patch(route('ai-chat.conversations.update', $conversation), $data);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);

        $this->assertDatabaseHas('ai_chat_conversations', [
            'id' => $conversation->id,
            'title' => $data['title'],
        ]);
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $conversation = AiChatConversation::factory()
            ->for($student)
            ->create();

        $payload = array_merge([
            'title' => 'PHPについての質問',
        ], $overrides);

        $response = $this->actingAs($student)->patchJson(
            route('ai-chat.conversations.update', $conversation),
            $payload,
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'title 未指定で 422' => [
                ['title' => ''],
                'title',
            ],
            'title 101 文字で 422' => [
                ['title' => str_repeat('a', 101)],
                'title',
            ],
        ];
    }
}
