<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\AiChat;

use App\Enums\AiChatMessageRole;
use App\Enums\AiChatMessageStatus;
use App\Exceptions\AiChat\AiChatDailyLimitExceededException;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\Certification;
use App\Models\Chapter;
use App\Models\Enrollment;
use App\Models\Part;
use App\Models\Section;
use App\Models\User;
use App\Services\GeminiService;
use App\UseCases\AiChat\StoreMessageAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class StoreMessageActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_user_and_assistant_messages_when_gemini_succeeds(): void
    {
        $student = User::factory()->student()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'auto_title_enabled' => false,
        ]);

        config([
            'ai-chat.gemini.model' => 'gemini-3.5-flash-lite',
        ]);

        $mock = Mockery::mock(GeminiService::class);

        $mock->shouldReceive('generate')
            ->once()
            ->andReturn([
                'content' => 'PHPの配列についての回答です。',
                'model' => 'gemini-3.5-flash-lite',
                'input_tokens' => 10,
                'output_tokens' => 20,
                'response_time_ms' => 100,
            ]);

        $this->app->instance(GeminiService::class, $mock);

        $result = app(StoreMessageAction::class)(
            user: $student,
            conversation: $conversation,
            message: 'PHPの配列について教えてください。',
        );

        $userMessage = $result['user_message'];
        $assistantMessage = $result['assistant_message'];

        $this->assertSame(AiChatMessageRole::User, $userMessage->role);
        $this->assertSame(
            'PHPの配列について教えてください。',
            $userMessage->content,
        );
        $this->assertSame(
            AiChatMessageStatus::Completed,
            $userMessage->status,
        );

        $this->assertSame(
            AiChatMessageRole::Assistant,
            $assistantMessage->role,
        );
        $this->assertSame(
            'PHPの配列についての回答です。',
            $assistantMessage->content,
        );
        $this->assertSame(
            AiChatMessageStatus::Completed,
            $assistantMessage->status,
        );
        $this->assertSame(
            'gemini-3.5-flash-lite',
            $assistantMessage->model,
        );
        $this->assertSame(10, $assistantMessage->input_tokens);
        $this->assertSame(20, $assistantMessage->output_tokens);
        $this->assertSame(100, $assistantMessage->response_time_ms);
        $this->assertNull($assistantMessage->error_detail);

        $this->assertDatabaseHas('ai_chat_messages', [
            'id' => $userMessage->id,
            'ai_chat_conversation_id' => $conversation->id,
            'role' => AiChatMessageRole::User->value,
            'content' => 'PHPの配列について教えてください。',
            'status' => AiChatMessageStatus::Completed->value,
        ]);

        $this->assertDatabaseHas('ai_chat_messages', [
            'id' => $assistantMessage->id,
            'ai_chat_conversation_id' => $conversation->id,
            'role' => AiChatMessageRole::Assistant->value,
            'content' => 'PHPの配列についての回答です。',
            'status' => AiChatMessageStatus::Completed->value,
            'model' => 'gemini-3.5-flash-lite',
            'input_tokens' => 10,
            'output_tokens' => 20,
            'response_time_ms' => 100,
        ]);
    }

    public function test_keeps_user_message_and_marks_assistant_message_as_error_when_gemini_fails(): void
    {
        $student = User::factory()->student()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'auto_title_enabled' => false,
        ]);

        $mock = Mockery::mock(GeminiService::class);

        $mock->shouldReceive('generate')
            ->once()
            ->andThrow(new RuntimeException('Gemini API error'));

        $this->app->instance(GeminiService::class, $mock);

        $result = app(StoreMessageAction::class)(
            user: $student,
            conversation: $conversation,
            message: 'PHPとはなんですか？',
        );

        $userMessage = $result['user_message'];
        $assistantMessage = $result['assistant_message'];

        $this->assertSame(AiChatMessageRole::User, $userMessage->role);
        $this->assertSame('PHPとはなんですか？', $userMessage->content);
        $this->assertSame(
            AiChatMessageStatus::Completed,
            $userMessage->status,
        );

        $this->assertSame(
            AiChatMessageRole::Assistant,
            $assistantMessage->role,
        );
        $this->assertSame(
            AiChatMessageStatus::Error,
            $assistantMessage->status,
        );
        $this->assertSame(
            'Gemini API error',
            $assistantMessage->error_detail,
        );

        $this->assertDatabaseHas('ai_chat_messages', [
            'id' => $userMessage->id,
            'ai_chat_conversation_id' => $conversation->id,
            'role' => AiChatMessageRole::User->value,
            'content' => 'PHPとはなんですか？',
            'status' => AiChatMessageStatus::Completed->value,
        ]);

        $this->assertDatabaseHas('ai_chat_messages', [
            'id' => $assistantMessage->id,
            'ai_chat_conversation_id' => $conversation->id,
            'role' => AiChatMessageRole::Assistant->value,
            'status' => AiChatMessageStatus::Error->value,
            'error_detail' => 'Gemini API error',
        ]);
    }

    public function test_throws_exception_when_daily_message_limit_is_reached(): void
    {
        config([
            'ai-chat.daily_message_limit' => 50,
        ]);

        $student = User::factory()->student()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
        ]);

        AiChatMessage::factory()
            ->count(50)
            ->create([
                'ai_chat_conversation_id' => $conversation->id,
                'role' => AiChatMessageRole::User,
                'status' => AiChatMessageStatus::Completed,
            ]);

        $mock = Mockery::mock(GeminiService::class);

        $mock->shouldNotReceive('generate');

        $this->app->instance(GeminiService::class, $mock);

        $this->expectException(
            AiChatDailyLimitExceededException::class,
        );
        $this->expectExceptionMessage(
            '本日のAIチャットの送信上限に達しました。明日 0:00 以降に再度ご利用ください。'
        );

        try {
            app(StoreMessageAction::class)(
                user: $student,
                conversation: $conversation,
                message: '51件目のメッセージです。',
            );
        } finally {
            $this->assertDatabaseCount('ai_chat_messages', 50);

            $this->assertDatabaseMissing('ai_chat_messages', [
                'ai_chat_conversation_id' => $conversation->id,
                'content' => '51件目のメッセージです。',
            ]);
        }
    }

    public function test_sends_recent_conversation_history_to_gemini(): void
    {
        config([
            'ai-chat.gemini.model' => 'gemini-3.5-flash-lite',
            'ai-chat.history_limit' => 2,
        ]);

        $student = User::factory()->student()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'auto_title_enabled' => false,
        ]);

        AiChatMessage::factory()->create([
            'ai_chat_conversation_id' => $conversation->id,
            'role' => AiChatMessageRole::User,
            'content' => '古い質問です。',
            'status' => AiChatMessageStatus::Completed,
        ]);

        AiChatMessage::factory()->create([
            'ai_chat_conversation_id' => $conversation->id,
            'role' => AiChatMessageRole::Assistant,
            'content' => '直前のAI回答です。',
            'status' => AiChatMessageStatus::Completed,
        ]);

        AiChatMessage::factory()->create([
            'ai_chat_conversation_id' => $conversation->id,
            'role' => AiChatMessageRole::User,
            'content' => '直前の質問です。',
            'status' => AiChatMessageStatus::Completed,
        ]);

        $mock = Mockery::mock(GeminiService::class);

        $mock->shouldReceive('generate')
            ->once()
            ->withArgs(function (string $message, array $history, ?string $context): bool {
                return $message === '今回の質問です。'
                    && $history === [
                        [
                            'role' => 'assistant',
                            'content' => '直前のAI回答です。',
                        ],
                        [
                            'role' => 'user',
                            'content' => '直前の質問です。',
                        ],
                    ];
            })
            ->andReturn([
                'content' => '今回のAI回答です。',
                'model' => 'gemini-3.5-flash-lite',
                'input_tokens' => 10,
                'output_tokens' => 20,
                'response_time_ms' => 100,
            ]);

        $this->app->instance(GeminiService::class, $mock);

        app(StoreMessageAction::class)(
            user: $student,
            conversation: $conversation,
            message: '今回の質問です。',
        );
    }

    public function test_sends_section_context_to_gemini(): void
    {
        config([
            'ai-chat.gemini.model' => 'gemini-3.5-flash-lite',
        ]);

        $student = User::factory()->student()->create();

        $certification = Certification::factory()->published()->create([
            'name' => 'PHP技術者認定試験',
        ]);

        $part = Part::factory()->for($certification)->create([
            'title' => 'PHP基礎',
        ]);

        $chapter = Chapter::factory()->for($part)->create([
            'title' => '配列',
        ]);

        $section = Section::factory()->for($chapter)->create([
            'title' => '連想配列',
            'description' => '連想配列の基本を学習します。',
            'body' => '連想配列はキーと値の組み合わせでデータを管理します。',
        ]);

        $enrollment = Enrollment::factory()
            ->for($student)
            ->for($certification)
            ->learning()
            ->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'section_id' => $section->id,
            'auto_title_enabled' => false,
        ]);

        $mock = Mockery::mock(GeminiService::class);

        $mock->shouldReceive('generate')
            ->once()
            ->withArgs(function (string $message, array $history, ?string $context): bool {
                return $message === '連想配列について教えてください。'
                    && $history === []
                    && is_string($context)
                    && str_contains(
                        $context,
                        '資格: PHP技術者認定試験',
                    )
                    && str_contains(
                        $context,
                        'パート: PHP基礎',
                    )
                    && str_contains(
                        $context,
                        'チャプター: 配列',
                    )
                    && str_contains(
                        $context,
                        '教材タイトル: 連想配列',
                    )
                    && str_contains(
                        $context,
                        '教材概要: 連想配列の基本を学習します。',
                    )
                    && str_contains(
                        $context,
                        '教材内容: 連想配列はキーと値の組み合わせでデータを管理します。',
                    );
            })
            ->andReturn([
                'content' => '連想配列についての回答です。',
                'model' => 'gemini-3.5-flash-lite',
                'input_tokens' => 10,
                'output_tokens' => 20,
                'response_time_ms' => 100,
            ]);

        $this->app->instance(GeminiService::class, $mock);

        app(StoreMessageAction::class)(
            user: $student,
            conversation: $conversation,
            message: '連想配列について教えてください。',
        );
    }

    public function test_sends_certification_context_to_gemini_for_general_conversation_with_enrollment(): void
    {
        config([
            'ai-chat.gemini.model' => 'gemini-3.5-flash-lite',
        ]);

        $student = User::factory()->student()->create();

        $certification = Certification::factory()->published()->create([
            'name' => 'PHP技術者認定試験',
        ]);

        $enrollment = Enrollment::factory()
            ->for($student)
            ->for($certification)
            ->learning()
            ->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'section_id' => null,
            'auto_title_enabled' => false,
        ]);

        $mock = Mockery::mock(GeminiService::class);

        $mock->shouldReceive('generate')
            ->once()
            ->withArgs(function (string $message, array $history, ?string $context): bool {
                return $message === 'この資格の勉強方法を教えてください。'
                    && $history === []
                    && is_string($context)
                    && str_contains(
                        $context,
                        'あなたは受講生の学習を支援するAIアシスタントです。',
                    )
                    && str_contains(
                        $context,
                        '以下の資格情報を前提として、受講生の質問に回答してください。',
                    )
                    && str_contains(
                        $context,
                        '資格: PHP技術者認定試験',
                    );
            })
            ->andReturn([
                'content' => '資格についての回答です。',
                'model' => 'gemini-3.5-flash-lite',
                'input_tokens' => 10,
                'output_tokens' => 20,
                'response_time_ms' => 100,
            ]);

        $this->app->instance(GeminiService::class, $mock);

        app(StoreMessageAction::class)(
            user: $student,
            conversation: $conversation,
            message: 'この資格の勉強方法を教えてください。',
        );
    }

    public function test_sends_generic_context_to_gemini_for_general_conversation_without_enrollment(): void
    {
        config([
            'ai-chat.gemini.model' => 'gemini-3.5-flash-lite',
        ]);

        $student = User::factory()->student()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'enrollment_id' => null,
            'section_id' => null,
            'auto_title_enabled' => false,
        ]);

        $mock = Mockery::mock(GeminiService::class);

        $mock->shouldReceive('generate')
            ->once()
            ->withArgs(function (string $message, array $history, ?string $context): bool {
                return $message === '勉強方法を教えてください。'
                    && $history === []
                    && $context
                    === 'あなたは受講生の学習を支援するAIアシスタントです。';
            })
            ->andReturn([
                'content' => '学習についての回答です。',
                'model' => 'gemini-3.5-flash-lite',
                'input_tokens' => 10,
                'output_tokens' => 20,
                'response_time_ms' => 100,
            ]);

        $this->app->instance(GeminiService::class, $mock);

        app(StoreMessageAction::class)(
            user: $student,
            conversation: $conversation,
            message: '勉強方法を教えてください。',
        );
    }
}
