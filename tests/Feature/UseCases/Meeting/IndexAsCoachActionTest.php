<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Meeting;

use App\Models\Meeting;
use App\Models\User;
use App\UseCases\Meeting\IndexAsCoachAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexAsCoachActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_coachs_own_meetings(): void
    {
        $coach = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();

        $meeting = Meeting::factory()
            ->forCoach($coach)
            ->forStudent($student)
            ->create([
                'scheduled_at' => now()->addDays(3)->startOfHour(),
            ]);

        Meeting::factory()
            ->forCoach($otherCoach)
            ->forStudent($student)
            ->create([
                'scheduled_at' => now()->addDays(4)->startOfHour(),
            ]);

        $result = app(IndexAsCoachAction::class)(
            coach: $coach,
        );

        $this->assertCount(1, $result['meetings']);
        $this->assertTrue($result['meetings']->contains('id', $meeting->id));
    }

    public function test_filters_meetings_by_student(): void
    {
        $coach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $meeting = Meeting::factory()
            ->forCoach($coach)
            ->forStudent($student)
            ->create([
                'scheduled_at' => now()->addDays(3)->startOfHour(),
            ]);

        Meeting::factory()
            ->forCoach($coach)
            ->forStudent($otherStudent)
            ->create([
                'scheduled_at' => now()->addDays(4)->startOfHour(),
            ]);

        $result = app(IndexAsCoachAction::class)(
            coach: $coach,
            studentId: $student->id,
        );

        $this->assertCount(1, $result['meetings']);
        $this->assertTrue($result['meetings']->contains('id', $meeting->id));
    }
}
