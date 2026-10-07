<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\EnrollmentGoal;

use App\Models\EnrollmentGoal;
use App\UseCases\EnrollmentGoal\MarkAchievedAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkAchievedActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_goal_as_achieved(): void
    {
        $goal = EnrollmentGoal::factory()->create();

        app(MarkAchievedAction::class)($goal);

        $this->assertNotNull($goal->fresh()->achieved_at);
    }
}
