<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\AiChat;

use App\Models\AiChatConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreMessageRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_data(): void
    {
        config([
            'ai-chat.gemini.api_key' => 'test-api-key',
        ]);

        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'PHPについての回答です。'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 10,
                    'candidatesTokenCount' => 20,
                ],
            ], 200),
        ]);

        $student = User::factory()->student()->inProgress()->create();

        $conversation = AiChatConversation::factory()
            ->for($student)
            ->create();

        $data = [
            'content' => 'PHPについて教えてください。',
        ];

        $response = $this->actingAs($student)->postJson(route('ai-chat.conversations.messages.store', $conversation), $data);

        $response->assertStatus(200);
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $conversation = AiChatConversation::factory()
            ->for($student)
            ->create();

        $payload = array_merge([
            'content' => 'PHPについて教えてください。',
        ], $overrides);

        $response = $this->actingAs($student)->postJson(
            route('ai-chat.conversations.messages.store', $conversation),
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
            'content 未指定で 422' => [
                ['content' => ''],
                'content',
            ],
            'content 2001 文字で 422' => [
                ['content' => str_repeat('a', 2001)],
                'content',
            ],
        ];
    }
}
