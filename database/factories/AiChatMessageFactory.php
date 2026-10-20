<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AiChatMessageRole;
use App\Enums\AiChatMessageStatus;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiChatMessage>
 */
class AiChatMessageFactory extends Factory
{
    protected $model = AiChatMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ai_chat_conversation_id' => AiChatConversation::factory(),
            'role' => AiChatMessageRole::User,
            'content' => fake()->realText(120),
            'status' => AiChatMessageStatus::Completed,
            'model' => null,
            'input_tokens' => null,
            'output_tokens' => null,
            'response_time_ms' => null,
            'error_detail' => null,
        ];
    }

    public function user(): static
    {
        return $this->state(fn () => [
            'role' => AiChatMessageRole::User,
            'status' => AiChatMessageStatus::Completed,
            'model' => null,
            'input_tokens' => null,
            'output_tokens' => null,
            'response_time_ms' => null,
            'error_detail' => null,
        ]);
    }

    public function assistant(): static
    {
        return $this->state(fn () => [
            'role' => AiChatMessageRole::Assistant,
            'status' => AiChatMessageStatus::Completed,
            'model' => 'gemini-3.5-flash-lite',
            'input_tokens' => fake()->numberBetween(10, 500),
            'output_tokens' => fake()->numberBetween(10, 500),
            'response_time_ms' => fake()->numberBetween(100, 3000),
            'error_detail' => null,
        ]);
    }

    public function error(): static
    {
        return $this->state(fn () => [
            'role' => AiChatMessageRole::Assistant,
            'status' => AiChatMessageStatus::Error,
            'content' => '',
            'model' => 'gemini-3.5-flash-lite',
            'input_tokens' => null,
            'output_tokens' => null,
            'response_time_ms' => null,
            'error_detail' => 'Gemini API request failed.',
        ]);
    }
}
