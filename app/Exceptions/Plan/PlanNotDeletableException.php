<?php

declare(strict_types=1);

namespace App\Exceptions\Plan;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PlanNotDeletableException extends ConflictHttpException
{
    public static function becauseNotDraft(): self
    {
        return new self('下書き状態のプランのみ削除できます。先に下書きに戻すか、アーカイブを利用してください。');
    }

    public static function becauseHasUsers(): self
    {
        return new self('受講者が紐づいているプランは削除できません。');
    }

    private function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
