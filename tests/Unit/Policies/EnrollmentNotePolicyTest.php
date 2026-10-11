<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use App\Policies\EnrollmentNotePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnrollmentNotePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_coach_can_view_enrollment_notes(): void
    {
        $coach = User::factory()->coach()->create();
        $certification = Certification::factory()->create();
        $enrollment = Enrollment::factory()->for($certification)->create();
        $admin = User::factory()->admin()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $policy = new EnrollmentNotePolicy;

        $this->assertTrue($policy->viewAny($coach, $enrollment));
    }

    public function test_unassigned_coach_cannot_view_enrollment_notes(): void
    {
        $coach = User::factory()->coach()->create();
        $enrollment = Enrollment::factory()->create();

        $policy = new EnrollmentNotePolicy;

        $this->assertFalse($policy->viewAny($coach, $enrollment));
    }

    public function test_student_cannot_view_enrollment_notes(): void
    {
        $student = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->create();

        $policy = new EnrollmentNotePolicy;

        $this->assertFalse($policy->viewAny($student, $enrollment));
    }

    public function test_admin_can_view_enrollment_notes(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->create();

        $policy = new EnrollmentNotePolicy;

        $this->assertTrue($policy->viewAny($admin, $enrollment));
    }

    public function test_assigned_coach_can_create_enrollment_note(): void
    {
        $coach = User::factory()->coach()->create();
        $certification = Certification::factory()->create();
        $enrollment = Enrollment::factory()->for($certification)->create();
        $admin = User::factory()->admin()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $policy = new EnrollmentNotePolicy;

        $this->assertTrue($policy->create($coach, $enrollment));
    }

    public function test_unassigned_coach_cannot_create_enrollment_note(): void
    {
        $coach = User::factory()->coach()->create();
        $enrollment = Enrollment::factory()->create();

        $policy = new EnrollmentNotePolicy;

        $this->assertFalse($policy->create($coach, $enrollment));
    }

    public function test_student_cannot_create_enrollment_note(): void
    {
        $student = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->create();

        $policy = new EnrollmentNotePolicy;

        $this->assertFalse($policy->create($student, $enrollment));
    }

    public function test_admin_can_create_enrollment_note(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->create();

        $policy = new EnrollmentNotePolicy;

        $this->assertTrue($policy->create($admin, $enrollment));
    }

    public function test_assigned_coach_can_update_own_enrollment_note(): void
    {
        $coach = User::factory()->coach()->create();
        $certification = Certification::factory()->create();
        $enrollment = Enrollment::factory()->for($certification)->create();
        $admin = User::factory()->admin()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $note = EnrollmentNote::factory()->create([
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $coach->id,
        ]);

        $policy = new EnrollmentNotePolicy;

        $this->assertTrue($policy->update($coach, $note));
    }

    public function test_assigned_coach_cannot_update_other_coachs_enrollment_note(): void
    {
        $coach = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();
        $certification = Certification::factory()->create();
        $enrollment = Enrollment::factory()->for($certification)->create();
        $admin = User::factory()->admin()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $certification->coaches()->attach($otherCoach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $note = EnrollmentNote::factory()->create([
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $otherCoach->id,
        ]);

        $policy = new EnrollmentNotePolicy;

        $this->assertFalse($policy->update($coach, $note));
    }

    public function test_unassigned_coach_cannot_update_enrollment_note(): void
    {
        $coach = User::factory()->coach()->create();
        $assignedCoach = User::factory()->coach()->create();
        $certification = Certification::factory()->create();
        $enrollment = Enrollment::factory()->for($certification)->create();
        $admin = User::factory()->admin()->create();

        $certification->coaches()->attach($assignedCoach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $note = EnrollmentNote::factory()->create([
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $assignedCoach->id,
        ]);

        $policy = new EnrollmentNotePolicy;

        $this->assertFalse($policy->update($coach, $note));
    }

    public function test_student_cannot_update_enrollment_note(): void
    {
        $student = User::factory()->student()->create();
        $note = EnrollmentNote::factory()->create();

        $policy = new EnrollmentNotePolicy;

        $this->assertFalse($policy->update($student, $note));
    }

    public function test_admin_can_update_enrollment_note(): void
    {
        $admin = User::factory()->admin()->create();
        $note = EnrollmentNote::factory()->create();

        $policy = new EnrollmentNotePolicy;

        $this->assertTrue($policy->update($admin, $note));
    }

    public function test_assigned_coach_can_delete_own_enrollment_note(): void
    {
        $coach = User::factory()->coach()->create();
        $certification = Certification::factory()->create();
        $enrollment = Enrollment::factory()->for($certification)->create();
        $admin = User::factory()->admin()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $note = EnrollmentNote::factory()->create([
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $coach->id,
        ]);

        $policy = new EnrollmentNotePolicy;

        $this->assertTrue($policy->delete($coach, $note));
    }

    public function test_assigned_coach_cannot_delete_other_coachs_enrollment_note(): void
    {
        $coach = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();
        $certification = Certification::factory()->create();
        $enrollment = Enrollment::factory()->for($certification)->create();
        $admin = User::factory()->admin()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $certification->coaches()->attach($otherCoach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $note = EnrollmentNote::factory()->create([
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $otherCoach->id,
        ]);

        $policy = new EnrollmentNotePolicy;

        $this->assertFalse($policy->delete($coach, $note));
    }

    public function test_unassigned_coach_cannot_delete_enrollment_note(): void
    {
        $coach = User::factory()->coach()->create();
        $assignedCoach = User::factory()->coach()->create();
        $certification = Certification::factory()->create();
        $enrollment = Enrollment::factory()->for($certification)->create();
        $admin = User::factory()->admin()->create();

        $certification->coaches()->attach($assignedCoach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $note = EnrollmentNote::factory()->create([
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $assignedCoach->id,
        ]);

        $policy = new EnrollmentNotePolicy;

        $this->assertFalse($policy->delete($coach, $note));
    }

    public function test_student_cannot_delete_enrollment_note(): void
    {
        $student = User::factory()->student()->create();
        $note = EnrollmentNote::factory()->create();

        $policy = new EnrollmentNotePolicy;

        $this->assertFalse($policy->delete($student, $note));
    }

    public function test_admin_can_delete_enrollment_note(): void
    {
        $admin = User::factory()->admin()->create();
        $note = EnrollmentNote::factory()->create();

        $policy = new EnrollmentNotePolicy;

        $this->assertTrue($policy->delete($admin, $note));
    }
}
