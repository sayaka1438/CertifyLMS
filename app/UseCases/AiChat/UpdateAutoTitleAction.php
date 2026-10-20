<?php

declare(strict_types=1);

namespace App\UseCases\AiChat;

use App\Models\AiChatConversation;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;
use Throwable;

final class UpdateAutoTitleAction
{
    public function __construct(
        private readonly GeminiService $geminiService,
    ) {}

    public function __invoke(
        AiChatConversation $conversation,
        string $userMessage,
        string $assistantMessage,
    ): void {
        if (! $conversation->auto_title_enabled) {
            return;
        }

        try {
            $result = $this->geminiService->generate(
                message: implode("\n", [
                    '以下の会話内容を表す簡潔な日本語のタイトルを作成してください。',
                    'タイトルだけを回答してください。',
                    '100文字以内にしてください。',
                    '',
                    '受講生:',
                    $userMessage,
                    '',
                    'AI:',
                    $assistantMessage,
                ]),
            );

            $title = mb_substr(trim($result['content']), 0, 100);

            if ($title === '') {
                return;
            }

            $conversation->forceFill([
                'title' => $title,
                'auto_title_enabled' => false,
            ])->save();
        } catch (Throwable $e) {
            Log::channel('ai-chat')->warning('AI chat title generation failed.', [
                'conversation_id' => $conversation->id,
                'exception' => $e,
            ]);
        }
    }
}
