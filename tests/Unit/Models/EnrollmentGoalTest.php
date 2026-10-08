<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EnrollmentGoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_relation_returns_owner_enrollment(): void
    {
        $enrollment = Enrollment::factory()->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $owner = $goal->enrollment;

        $this->assertTrue($owner->is($enrollment));
    }

    public function test_target_date_cast_returns_carbon_date(): void
    {
        $goal = EnrollmentGoal::factory()->create([
            'target_date' => '2024-01-01',
        ]);

        $this->assertInstanceOf(Carbon::class, $goal->target_date);
        $this->assertSame('2024-01-01', $goal->target_date->toDateString());
    }

    public function test_achieved_at_cast_returns_carbon_datetime(): void
    {
        $goal = EnrollmentGoal::factory()->achieved()->create();

        $this->assertInstanceOf(Carbon::class, $goal->achieved_at);
    }

    public function test_scope_display_order_sorts_goals_in_display_order(): void
    {
        $enrollment = Enrollment::factory()->create();

        $goalA = EnrollmentGoal::factory()->for($enrollment)->create([
            'target_date' => '2026-10-20',
        ]);

        $goalB = EnrollmentGoal::factory()->for($enrollment)->achieved()->create([
            'target_date' => '2026-10-10',
        ]);

        $goalC = EnrollmentGoal::factory()->for($enrollment)->create([
            'target_date' => null,
        ]);

        $goalD = EnrollmentGoal::factory()->for($enrollment)->create([
            'target_date' => '2026-10-15',
        ]);

        $result = $enrollment->goals()
            ->displayOrder()
            ->get();

        $this->assertSame([
            $goalD->id,
            $goalA->id,
            $goalC->id,
            $goalB->id,
        ], $result->pluck('id')->all());
    }

    public function test_scope_display_order_sorts_same_target_date_by_latest_created(): void
    {
        $enrollment = Enrollment::factory()->create();

        $older = EnrollmentGoal::factory()->for($enrollment)->create([
            'target_date' => '2026-10-20',
            'created_at' => now()->subDay(),
        ]);

        $newer = EnrollmentGoal::factory()->for($enrollment)->create([
            'target_date' => '2026-10-20',
            'created_at' => now(),
        ]);

        $results = $enrollment->goals()
            ->displayOrder()
            ->get();

        $this->assertTrue($results->first()->is($newer));
        $this->assertTrue($results->last()->is($older));
    }

    public function test_is_achieved_returns_achievement_status(): void
    {
        $achievedGoal = EnrollmentGoal::factory()->achieved()->create();
        $notAchievedGoal = EnrollmentGoal::factory()->create();

        $this->assertTrue($achievedGoal->isAchieved());
        $this->assertFalse($notAchievedGoal->isAchieved());
    }
}
