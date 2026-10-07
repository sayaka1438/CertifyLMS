<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\EnrollmentGoal;

use App\Models\Enrollment;
use App\UseCases\EnrollmentGoal\StoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_goal_for_enrollment(): void
    {
        $enrollment = Enrollment::factory()->create();
        $validated = [
            'title' => 'Laravelの基礎を習得する',
            'description' => '毎日教材を進める',
            'target_date' => '2026-10-31',
        ];

        $result = app(StoreAction::class)(
            enrollment: $enrollment,
            validated: $validated,
        );

        $this->assertSame($enrollment->id, $result->enrollment_id);
        $this->assertSame($validated['title'], $result->title);
        $this->assertSame($validated['description'], $result->description);
        $this->assertSame($validated['target_date'], $result->target_date->toDateString());
    }
}
