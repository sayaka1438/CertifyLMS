<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\EnrollmentGoal;

use App\Models\EnrollmentGoal;
use App\UseCases\EnrollmentGoal\DestroyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_goal(): void
    {
        $goal = EnrollmentGoal::factory()->create();

        $goalId = $goal->id;

        app(DestroyAction::class)($goal);

        $this->assertDatabaseMissing('enrollment_goals', ['id' => $goalId]);
    }
}
