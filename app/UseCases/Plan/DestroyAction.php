<?php

declare(strict_types=1);

namespace App\UseCases\Plan;

use App\Enums\PlanStatus;
use App\Exceptions\Plan\PlanNotDeletableException;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

final class DestroyAction
{
    public function __invoke(Plan $plan): void
    {
        if (
            $plan->status !== PlanStatus::Draft
            || $plan->users()->exists()
            // TODO: PM確認後,利用履歴も削除条件に含む場合は実装
            // || $plan->userPlanLogs()->exists()
        ) {
            throw new PlanNotDeletableException;
        }

        DB::transaction(fn () => $plan->delete());
    }
}
