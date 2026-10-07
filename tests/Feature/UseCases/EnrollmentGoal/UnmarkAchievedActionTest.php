<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\EnrollmentGoal;

use App\Models\EnrollmentGoal;
use App\UseCases\EnrollmentGoal\UnmarkAchievedAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnmarkAchievedActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_achieved_goal_as_unachieved(): void
    {
        $goal = EnrollmentGoal::factory()->achieved()->create();

        app(UnmarkAchievedAction::class)($goal);

        $this->assertNull($goal->fresh()->achieved_at);
    }
}
