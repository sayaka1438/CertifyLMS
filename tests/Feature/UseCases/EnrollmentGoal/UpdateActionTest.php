<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\EnrollmentGoal;

use App\Models\EnrollmentGoal;
use App\UseCases\EnrollmentGoal\UpdateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_goal(): void
    {
        $goal = EnrollmentGoal::factory()->create();
        $validated = [
            'title' => 'Laravelの基礎を習得する',
            'description' => '毎日教材を進める',
            'target_date' => '2026-10-31',
        ];

        $result = app(UpdateAction::class)(
            goal: $goal,
            validated: $validated,
        );

        $this->assertSame($validated['title'], $result->title);
        $this->assertSame($validated['description'], $result->description);
        $this->assertSame($validated['target_date'], $result->target_date->toDateString());
    }
}
