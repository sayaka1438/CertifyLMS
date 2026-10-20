<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\AiChat;

use App\Models\AiChatConversation;
use App\Services\GeminiService;
use App\UseCases\AiChat\UpdateAutoTitleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class UpdateAutoTitleActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_title_and_disables_auto_title_when_generation_succeeds(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'title' => '新規相談',
            'auto_title_enabled' => true,
        ]);

        $mock = Mockery::mock(GeminiService::class);

        $mock->shouldReceive('generate')
            ->once()
            ->andReturn([
                'content' => 'PHPの配列について',
                'model' => 'gemini-3.5-flash-lite',
                'input_tokens' => 10,
                'output_tokens' => 5,
                'response_time_ms' => 100,
            ]);

        $this->app->instance(GeminiService::class, $mock);

        app(UpdateAutoTitleAction::class)(
            conversation: $conversation,
            userMessage: 'PHPの配列について教えてください。',
            assistantMessage: 'PHPの配列には通常の配列や連想配列があります。',
        );

        $conversation->refresh();

        $this->assertSame('PHPの配列について', $conversation->title);
        $this->assertFalse($conversation->auto_title_enabled);

        $this->assertDatabaseHas('ai_chat_conversations', [
            'id' => $conversation->id,
            'title' => 'PHPの配列について',
            'auto_title_enabled' => false,
        ]);
    }

    public function test_does_not_generate_title_when_auto_title_is_disabled(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'title' => '手動で設定したタイトル',
            'auto_title_enabled' => false,
        ]);

        $mock = Mockery::mock(GeminiService::class);

        $mock->shouldNotReceive('generate');

        $this->app->instance(GeminiService::class, $mock);

        app(UpdateAutoTitleAction::class)(
            conversation: $conversation,
            userMessage: 'PHPについて教えてください。',
            assistantMessage: 'PHPについての回答です。',
        );

        $conversation->refresh();

        $this->assertSame(
            '手動で設定したタイトル',
            $conversation->title,
        );
        $this->assertFalse($conversation->auto_title_enabled);
    }

    public function test_does_not_update_title_when_generated_title_is_empty(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'title' => '新規相談',
            'auto_title_enabled' => true,
        ]);

        $mock = Mockery::mock(GeminiService::class);

        $mock->shouldReceive('generate')
            ->once()
            ->andReturn([
                'content' => '   ',
                'model' => 'gemini-3.5-flash-lite',
                'input_tokens' => 10,
                'output_tokens' => 1,
                'response_time_ms' => 100,
            ]);

        $this->app->instance(GeminiService::class, $mock);

        app(UpdateAutoTitleAction::class)(
            conversation: $conversation,
            userMessage: 'PHPについて教えてください。',
            assistantMessage: 'PHPについての回答です。',
        );

        $conversation->refresh();

        $this->assertSame('新規相談', $conversation->title);
        $this->assertTrue($conversation->auto_title_enabled);
    }

    public function test_trims_and_limits_generated_title_to_100_characters(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'title' => '新規相談',
            'auto_title_enabled' => true,
        ]);

        $generatedTitle = '  '.str_repeat('あ', 101).'  ';

        $mock = Mockery::mock(GeminiService::class);

        $mock->shouldReceive('generate')
            ->once()
            ->andReturn([
                'content' => $generatedTitle,
                'model' => 'gemini-3.5-flash-lite',
                'input_tokens' => 10,
                'output_tokens' => 10,
                'response_time_ms' => 100,
            ]);

        $this->app->instance(GeminiService::class, $mock);

        app(UpdateAutoTitleAction::class)(
            conversation: $conversation,
            userMessage: 'PHPについて教えてください。',
            assistantMessage: 'PHPについての回答です。',
        );

        $conversation->refresh();

        $this->assertSame(
            str_repeat('あ', 100),
            $conversation->title,
        );
        $this->assertSame(
            100,
            mb_strlen($conversation->title),
        );
        $this->assertFalse($conversation->auto_title_enabled);
    }

    public function test_keeps_existing_title_when_title_generation_fails(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'title' => '新規相談',
            'auto_title_enabled' => true,
        ]);

        $mock = Mockery::mock(GeminiService::class);

        $mock->shouldReceive('generate')
            ->once()
            ->andThrow(
                new RuntimeException('Gemini API error')
            );

        $this->app->instance(GeminiService::class, $mock);

        app(UpdateAutoTitleAction::class)(
            conversation: $conversation,
            userMessage: 'PHPについて教えてください。',
            assistantMessage: 'PHPについての回答です。',
        );

        $conversation->refresh();

        $this->assertSame('新規相談', $conversation->title);
        $this->assertTrue($conversation->auto_title_enabled);
    }
}
