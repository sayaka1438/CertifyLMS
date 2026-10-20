<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Http\Middleware\EnsureAiChatEnabled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureAiChatEnabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(EnsureAiChatEnabled::class)
            ->get('/__test/ensure-ai-chat-enabled', fn () => 'ok')
            ->name('__test.ensure-ai-chat-enabled');
    }

    public function test_ai_chat_enabled_passes(): void
    {
        config(['ai-chat.enabled' => true]);

        $this->get('/__test/ensure-ai-chat-enabled')
            ->assertOk()
            ->assertSee('ok');
    }

    public function test_ai_chat_disabled_returns_not_found(): void
    {
        config(['ai-chat.enabled' => false]);

        $this->get('/__test/ensure-ai-chat-enabled')
            ->assertNotFound();
    }
}
