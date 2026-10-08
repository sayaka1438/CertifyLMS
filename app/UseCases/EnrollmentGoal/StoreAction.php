<?php

declare(strict_types=1);

namespace App\UseCases\EnrollmentGoal;

use App\Models\Enrollment;
use App\Models\EnrollmentGoal;

final class StoreAction
{
    /**
     * @param array{
     *     title: string,
     *     description?: string|null,
     *     target_date?: string|null,
     * } $validated
     */
    public function __invoke(Enrollment $enrollment, array $validated): EnrollmentGoal
    {
        $goal = $enrollment->goals()->create($validated);

        return $goal;
    }
}
