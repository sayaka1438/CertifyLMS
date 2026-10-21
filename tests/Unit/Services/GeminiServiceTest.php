<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\GeminiService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    public function test_generates_content_from_gemini_response(): void
    {
        config([
            'ai-chat.gemini.api_key' => 'test-api-key',
            'ai-chat.gemini.model' => 'gemini-3.5-flash-lite',
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

        $result = app(GeminiService::class)->generate(
            message: 'PHPについて教えてください。',
        );

        $this->assertSame('PHPについての回答です。', $result['content']);
        $this->assertSame('gemini-3.5-flash-lite', $result['model']);
        $this->assertSame(10, $result['input_tokens']);
        $this->assertSame(20, $result['output_tokens']);
        $this->assertIsInt($result['response_time_ms']);
        $this->assertGreaterThanOrEqual(0, $result['response_time_ms']);
    }

    public function test_sends_message_and_history_to_gemini(): void
    {
        config([
            'ai-chat.gemini.api_key' => 'test-api-key',
            'ai-chat.gemini.model' => 'gemini-3.5-flash-lite',
        ]);

        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '今回の回答です。'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        app(GeminiService::class)->generate(
            message: '今回の質問です。',
            history: [
                [
                    'role' => 'user',
                    'content' => '最初の質問です。',
                ],
                [
                    'role' => 'assistant',
                    'content' => '最初の回答です。',
                ],
            ],
        );

        Http::assertSent(function (Request $request): bool {
            $contents = $request->data()['contents'] ?? [];

            return $request->url()
                === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent'
                && $request->hasHeader('x-goog-api-key', 'test-api-key')
                && count($contents) === 3
                && $contents[0]['role'] === 'user'
                && $contents[0]['parts'][0]['text'] === '最初の質問です。'
                && $contents[1]['role'] === 'model'
                && $contents[1]['parts'][0]['text'] === '最初の回答です。'
                && $contents[2]['role'] === 'user'
                && $contents[2]['parts'][0]['text'] === '今回の質問です。';
        });
    }

    public function test_sends_system_instruction_when_context_is_provided(): void
    {
        config([
            'ai-chat.gemini.api_key' => 'test-api-key',
            'ai-chat.gemini.model' => 'gemini-3.5-flash-lite',
        ]);

        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '回答です。'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        app(GeminiService::class)->generate(
            message: '質問です。',
            context: 'PHP技術者認定試験について回答してください。',
        );

        Http::assertSent(function (Request $request): bool {
            $systemInstruction = $request->data()['systemInstruction'] ?? null;

            return $systemInstruction === [
                'parts' => [
                    [
                        'text' => 'PHP技術者認定試験について回答してください。',
                    ],
                ],
            ];
        });
    }

    public function test_does_not_send_system_instruction_when_context_is_not_provided(): void
    {
        config([
            'ai-chat.gemini.api_key' => 'test-api-key',
            'ai-chat.gemini.model' => 'gemini-3.5-flash-lite',
        ]);

        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '回答です。'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        app(GeminiService::class)->generate(
            message: '質問です。',
        );

        Http::assertSent(function (Request $request): bool {
            return ! array_key_exists(
                'systemInstruction',
                $request->data(),
            );
        });
    }

    public function test_throws_request_exception_when_gemini_returns_http_error(): void
    {
        config([
            'ai-chat.gemini.api_key' => 'test-api-key',
            'ai-chat.gemini.model' => 'gemini-3.5-flash-lite',
        ]);

        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $this->expectException(RequestException::class);

        app(GeminiService::class)->generate(
            message: '質問です。',
        );
    }

    public function test_throws_runtime_exception_when_response_content_is_missing(): void
    {
        config([
            'ai-chat.gemini.api_key' => 'test-api-key',
            'ai-chat.gemini.model' => 'gemini-3.5-flash-lite',
        ]);

        Http::fake([
            '*' => Http::response([
                'candidates' => [],
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Gemini response content is missing.');

        app(GeminiService::class)->generate(
            message: '質問です。',
        );
    }
}
