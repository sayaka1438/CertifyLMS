<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Http\Middleware\EnsureAiChatConfigured;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureAiChatConfiguredTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(EnsureAiChatConfigured::class)
            ->get('/__test/ensure-ai-chat-configured', fn () => 'ok')
            ->name('__test.ensure-ai-chat-configured');
    }

    public function test_ai_chat_configured_passes(): void
    {
        config(['ai-chat.gemini.api_key' => 'test-api-key']);

        $this->get('/__test/ensure-ai-chat-configured')
            ->assertOk()
            ->assertSee('ok');
    }

    public function test_ai_chat_not_configured_returns_internal_server_error(): void
    {
        config(['ai-chat.gemini.api_key' => '']);

        $this->get('/__test/ensure-ai-chat-configured')
            ->assertStatus(500)
            ->assertSee('AI 相談機能は現在ご利用いただけません');
    }
}
