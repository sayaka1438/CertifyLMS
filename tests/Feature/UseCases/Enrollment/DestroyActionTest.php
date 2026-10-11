<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Enrollment;

use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Models\EnrollmentNote;
use App\UseCases\Enrollment\DestroyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_related_data_when_enrollment_is_deleted(): void
    {
        $enrollment = Enrollment::factory()->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();
        $note = EnrollmentNote::factory()->for($enrollment)->create();

        $goalId = $goal->id;
        $noteId = $note->id;

        app(DestroyAction::class)($enrollment);

        $this->assertSoftDeleted('enrollments', [
            'id' => $enrollment->id,
        ]);
        $this->assertDatabaseMissing('enrollment_goals', [
            'id' => $goalId,
        ]);
        $this->assertDatabaseMissing('enrollment_notes', [
            'id' => $noteId,
        ]);
    }
}
