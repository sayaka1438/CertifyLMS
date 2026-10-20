<?php

declare(strict_types=1);

namespace App\Exceptions\AiChat;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class AiChatNotConfiguredException extends HttpException
{
    public function __construct()
    {
        parent::__construct(
            500,
            'AI 相談機能は現在ご利用いただけません',
        );
    }

    public function render(): Response
    {
        return response()->view('errors._layout', [
            'code' => '500',
            'heading' => 'AI 相談機能は現在ご利用いただけません',
            'description' => '',
        ], 500);
    }
}
