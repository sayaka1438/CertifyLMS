<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Meeting;

use App\Models\Meeting;
use App\Models\User;
use App\UseCases\Meeting\IndexAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_students_own_meetings(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $coach = User::factory()->coach()->create();

        $meeting = Meeting::factory()->forCoach($coach)->forStudent($student)->create([
            'scheduled_at' => now()->addDays(3)->startOfHour(),
        ]);

        Meeting::factory()->forCoach($coach)->forStudent($otherStudent)->create([
            'scheduled_at' => now()->addDays(4)->startOfHour(),
        ]);

        $result = app(IndexAction::class)(
            student: $student,
        );

        $this->assertCount(1, $result['meetings']);
        $this->assertTrue($result['meetings']->contains('id', $meeting->id));
    }

    public function test_returns_remaining_meeting_quota(): void
    {
        $student = User::factory()->student()->create([
            'max_meetings' => 5,
        ]);

        $result = app(IndexAction::class)(
            student: $student,
        );

        $this->assertSame(5, $result['meetingsRemaining']);
    }
}
