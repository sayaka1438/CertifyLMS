<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\AiChat;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreConversationRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_data(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $data = [
            'section_id' => null,
        ];

        $response = $this->actingAs($student)->post(route('ai-chat.conversations.store'), $data);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);

        $this->assertDatabaseHas('ai_chat_conversations', [
            'user_id' => $student->id,
            'section_id' => null,
        ]);
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $payload = array_merge([
            'section_id' => null,
        ], $overrides);

        $response = $this->actingAs($student)->postJson(route('ai-chat.conversations.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'section_id が ULID 形式でない場合 422' => [
                ['section_id' => 'invalid-id'],
                'section_id',
            ],
            'section_id が存在しない場合 422' => [
                ['section_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV'],
                'section_id',
            ],
            'message 2001 文字で 422' => [
                ['message' => str_repeat('a', 2001)],
                'message',
            ],
        ];
    }
}
