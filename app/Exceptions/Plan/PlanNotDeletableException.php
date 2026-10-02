<?php

declare(strict_types=1);

namespace App\Exceptions\Plan;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PlanNotDeletableException extends ConflictHttpException
{
    // TODO: PM確認後、利用履歴も削除条件に含む場合はメッセージを修正する
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('下書きかつ受講者が紐づいていないプランのみ削除できます。', $previous);
    }
}
