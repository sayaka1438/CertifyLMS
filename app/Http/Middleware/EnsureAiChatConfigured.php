<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\AiChat\AiChatNotConfiguredException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAiChatConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = config('ai-chat.gemini.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new AiChatNotConfiguredException;
        }

        return $next($request);
    }
}
