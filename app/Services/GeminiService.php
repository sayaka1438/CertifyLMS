<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    /**
     * @param array<int, array{role: string, content: string}> $history
     *
     * @return array{
     *     content: string,
     *     model: string,
     *     input_tokens: int|null,
     *     output_tokens: int|null,
     *     response_time_ms: int
     * }
     */
    public function generate(
        string $message,
        array $history = [],
        ?string $context = null,
    ): array {
        $apiKey = config('ai-chat.gemini.api_key');
        $model = (string) config('ai-chat.gemini.model');

        $contents = collect($history)
            ->map(fn (array $item): array => [
                'role' => $item['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [
                    ['text' => $item['content']],
                ],
            ])
            ->values()
            ->all();

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $message],
            ],
        ];

        $payload = [
            'contents' => $contents,
        ];

        if ($context !== null && $context !== '') {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $context],
                ],
            ];
        }

        $startedAt = microtime(true);

        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
        ])->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
            $payload,
        );

        $responseTimeMs = (int) round(
            (microtime(true) - $startedAt) * 1000
        );

        $response->throw();

        $content = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($content) || $content === '') {
            throw new RuntimeException('Gemini response content is missing.');
        }

        return [
            'content' => $content,
            'model' => $model,
            'input_tokens' => $response->json('usageMetadata.promptTokenCount'),
            'output_tokens' => $response->json('usageMetadata.candidatesTokenCount'),
            'response_time_ms' => $responseTimeMs,
        ];
    }
}
