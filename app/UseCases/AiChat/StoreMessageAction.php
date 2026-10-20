<?php

declare(strict_types=1);

namespace App\UseCases\AiChat;

use App\Enums\AiChatMessageRole;
use App\Enums\AiChatMessageStatus;
use App\Exceptions\AiChat\AiChatDailyLimitExceededException;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;
use Throwable;

final class StoreMessageAction
{
    public function __construct(
        private readonly GeminiService $geminiService,
        private readonly UpdateAutoTitleAction $updateAutoTitle,
    ) {}

    /**
     * @return array{
     *     user_message: AiChatMessage,
     *     assistant_message: AiChatMessage,
     *     conversation: AiChatConversation
     * }
     */
    public function __invoke(
        User $user,
        AiChatConversation $conversation,
        string $message,
    ): array {
        $dailyLimit = (int) config('ai-chat.daily_message_limit');

        $todayMessageCount = AiChatMessage::query()
            ->where('role', AiChatMessageRole::User)
            ->whereHas('conversation', function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->whereDate('created_at', today())
            ->count();

        if ($todayMessageCount >= $dailyLimit) {
            throw new AiChatDailyLimitExceededException;
        }

        $userMessage = AiChatMessage::create([
            'ai_chat_conversation_id' => $conversation->id,
            'role' => AiChatMessageRole::User,
            'content' => $message,
            'status' => AiChatMessageStatus::Completed,
        ]);

        $assistantMessage = AiChatMessage::create([
            'ai_chat_conversation_id' => $conversation->id,
            'role' => AiChatMessageRole::Assistant,
            'content' => '',
            'status' => AiChatMessageStatus::Pending,
            'model' => config('ai-chat.gemini.model'),
        ]);

        $historyLimit = (int) config('ai-chat.history_limit');

        $history = AiChatMessage::query()
            ->where('ai_chat_conversation_id', $conversation->id)
            ->where('status', AiChatMessageStatus::Completed)
            ->where('id', '!=', $userMessage->id)
            ->latest('created_at')
            ->latest('id')
            ->limit($historyLimit)
            ->get()
            ->reverse()
            ->map(fn (AiChatMessage $historyMessage): array => [
                'role' => $historyMessage->role->value,
                'content' => $historyMessage->content,
            ])
            ->values()
            ->all();

        $conversation->loadMissing([
            'enrollment.certification',
            'section.chapter.part',
        ]);

        if ($conversation->section !== null) {
            $context = implode("\n", array_filter([
                'あなたは受講生の学習を支援するAIアシスタントです。',
                '以下の資格・教材情報を前提として、受講生の質問に回答してください。',
                '資格: '.$conversation->enrollment?->certification?->name,
                'パート: '.$conversation->section->chapter->part->title,
                'チャプター: '.$conversation->section->chapter->title,
                '教材タイトル: '.$conversation->section->title,
                '教材概要: '.$conversation->section->description,
                '教材内容: '.$conversation->section->body,
            ]));
        } elseif ($conversation->enrollment !== null) {
            $context = implode("\n", array_filter([
                'あなたは受講生の学習を支援するAIアシスタントです。',
                '以下の資格情報を前提として、受講生の質問に回答してください。',
                '資格: '.$conversation->enrollment->certification->name,
            ]));
        } else {
            $context = 'あなたは受講生の学習を支援するAIアシスタントです。';
        }

        try {
            $result = $this->geminiService->generate(
                message: $message,
                history: $history,
                context: $context,
            );

            $assistantMessage->forceFill([
                'content' => $result['content'],
                'status' => AiChatMessageStatus::Completed,
                'model' => $result['model'],
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
                'response_time_ms' => $result['response_time_ms'],
                'error_detail' => null,
            ])->save();

            ($this->updateAutoTitle)(
                conversation: $conversation,
                userMessage: $message,
                assistantMessage: $result['content'],
            );
        } catch (Throwable $e) {
            $assistantMessage->forceFill([
                'status' => AiChatMessageStatus::Error,
                'error_detail' => $e->getMessage(),
            ])->save();

            Log::channel('ai-chat')->error('Gemini API request failed.', [
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'message_id' => $assistantMessage->id,
                'exception' => $e,
            ]);
        }

        return [
            'user_message' => $userMessage->refresh(),
            'assistant_message' => $assistantMessage->refresh(),
            'conversation' => $conversation->refresh(),
        ];
    }
}
