<?php

declare(strict_types=1);

namespace App\Exceptions\Certification;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * 修了証 PDF の生成または private storage への保存に失敗した際の例外（HTTP 500）。
 */
final class CertificatePdfGenerationException extends HttpException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(
            500,
            '修了証の発行に失敗しました。時間をおいて再度お試しください。',
            $previous,
        );
    }

    public function render(): Response
    {
        return response()->view('errors._layout', [
            'code' => '500',
            'heading' => '修了証の発行に失敗しました。',
            'description' => '時間をおいて再度お試しください。',
        ], 500);
    }
}
