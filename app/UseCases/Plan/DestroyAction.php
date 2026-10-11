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
        if ($plan->status !== PlanStatus::Draft) {
            throw PlanNotDeletableException::becauseNotDraft();
        }

        if ($plan->users()->exists()) {
            throw PlanNotDeletableException::becauseHasUsers();
        }

        DB::transaction(fn () => $plan->delete());
    }
}
