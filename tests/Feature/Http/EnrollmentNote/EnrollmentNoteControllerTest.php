<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentNote;

use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnrollmentNoteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_note_for_assigned_coach(): void
    {
        [$coach, $enrollment] = $this->createEnrollmentWithAssignedCoach();

        $data = [
            'body' => '学習状況を確認しました。順調に進んでいます。',
        ];

        $response = $this->actingAs($coach)->post(route('enrollments.notes.store', $enrollment), $data);

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success', '受講生メモを追加しました。');

        $this->assertDatabaseHas('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $coach->id,
            'body' => $data['body'],
        ]);
    }

    public function test_store_forbids_unassigned_coach(): void
    {
        [, $enrollment] = $this->createEnrollmentWithAssignedCoach();
        $coach = User::factory()->coach()->create();

        $data = [
            'body' => '学習状況を確認しました。',
        ];

        $response = $this->actingAs($coach)->post(route('enrollments.notes.store', $enrollment), $data);

        $response->assertForbidden();

        $this->assertDatabaseMissing('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $coach->id,
            'body' => $data['body'],
        ]);
    }

    public function test_store_creates_note_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->create();

        $data = [
            'body' => '管理者として学習状況を確認しました。',
        ];

        $response = $this->actingAs($admin)->post(route('enrollments.notes.store', $enrollment), $data);

        $response->assertRedirect(route('enrollments.show', $enrollment));

        $this->assertDatabaseHas('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $admin->id,
            'body' => $data['body'],
        ]);
    }

    public function test_store_forbids_student(): void
    {
        $student = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->create();

        $data = [
            'body' => '学習状況を確認しました。',
        ];

        $response = $this->actingAs($student)->post(route('enrollments.notes.store', $enrollment), $data);

        $response->assertForbidden();
    }

    public function test_edit_allows_assigned_coach_for_own_note(): void
    {
        [$coach, $enrollment] = $this->createEnrollmentWithAssignedCoach();

        $note = EnrollmentNote::factory()->create([
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $coach->id,
        ]);

        $response = $this->actingAs($coach)->get(route('enrollment-notes.edit', $note));

        $response->assertOk();
        $response->assertViewIs('enrollment-note.edit');
        $response->assertViewHas('note', $note);
    }

    public function test_edit_forbids_assigned_coach_for_other_coachs_note(): void
    {
        [$coach, $enrollment] = $this->createEnrollmentWithAssignedCoach();
        $otherCoach = User::factory()->coach()->create();
        $admin = User::factory()->admin()->create();

        $enrollment->certification->coaches()->attach($otherCoach->id, [
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

        $response = $this->actingAs($coach)->get(route('enrollment-notes.edit', $note));

        $response->assertForbidden();
    }

    public function test_edit_allows_admin_for_any_note(): void
    {
        $admin = User::factory()->admin()->create();
        $note = EnrollmentNote::factory()->create();

        $response = $this->actingAs($admin)->get(route('enrollment-notes.edit', $note));

        $response->assertOk();
    }

    public function test_update_updates_own_note_for_assigned_coach(): void
    {
        [$coach, $enrollment] = $this->createEnrollmentWithAssignedCoach();

        $note = EnrollmentNote::factory()->create([
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $coach->id,
        ]);

        $data = [
            'body' => '学習状況を更新しました。',
        ];

        $response = $this->actingAs($coach)->patch(route('enrollment-notes.update', $note), $data);

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success', '受講生メモを更新しました。');

        $this->assertDatabaseHas('enrollment_notes', [
            'id' => $note->id,
            'body' => $data['body'],
        ]);
    }

    public function test_update_forbids_assigned_coach_for_other_coachs_note(): void
    {
        [$coach, $enrollment] = $this->createEnrollmentWithAssignedCoach();
        $otherCoach = User::factory()->coach()->create();
        $admin = User::factory()->admin()->create();

        $enrollment->certification->coaches()->attach($otherCoach->id, [
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

        $data = [
            'body' => '学習状況を更新しました。',
        ];

        $response = $this->actingAs($coach)->patch(route('enrollment-notes.update', $note), $data);

        $response->assertForbidden();

        $this->assertDatabaseHas('enrollment_notes', [
            'id' => $note->id,
            'body' => $note->body,
        ]);
    }

    public function test_update_allows_admin_for_any_note(): void
    {
        $admin = User::factory()->admin()->create();
        $note = EnrollmentNote::factory()->create();

        $data = [
            'body' => '管理者が受講生メモを更新しました。',
        ];

        $this->actingAs($admin)->patch(route('enrollment-notes.update', $note), $data);

        $this->assertDatabaseHas('enrollment_notes', [
            'id' => $note->id,
            'body' => $data['body'],
        ]);
    }

    public function test_destroy_deletes_own_note_for_assigned_coach(): void
    {
        [$coach, $enrollment] = $this->createEnrollmentWithAssignedCoach();

        $note = EnrollmentNote::factory()->create([
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $coach->id,
        ]);

        $response = $this->actingAs($coach)->delete(route('enrollment-notes.destroy', $note));

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success', '受講生メモを削除しました。');

        $this->assertDatabaseMissing('enrollment_notes', [
            'id' => $note->id,
        ]);
    }

    public function test_destroy_forbids_assigned_coach_for_other_coachs_note(): void
    {
        [$coach, $enrollment] = $this->createEnrollmentWithAssignedCoach();
        $otherCoach = User::factory()->coach()->create();
        $admin = User::factory()->admin()->create();

        $enrollment->certification->coaches()->attach($otherCoach->id, [
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

        $response = $this->actingAs($coach)->delete(route('enrollment-notes.destroy', $note));

        $response->assertForbidden();

        $this->assertDatabaseHas('enrollment_notes', [
            'id' => $note->id,
        ]);
    }

    public function test_destroy_allows_admin_for_any_note(): void
    {
        $admin = User::factory()->admin()->create();
        $note = EnrollmentNote::factory()->create();

        $this->actingAs($admin)->delete(route('enrollment-notes.destroy', $note));

        $this->assertDatabaseMissing('enrollment_notes', [
            'id' => $note->id,
        ]);
    }

    public function test_assigned_coach_can_view_other_coachs_note(): void
    {
        [$coach, $enrollment] = $this->createEnrollmentWithAssignedCoach();
        $otherCoach = User::factory()->coach()->create();
        $admin = User::factory()->admin()->create();

        $enrollment->certification->coaches()->attach($otherCoach->id, [
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

        $response = $this->actingAs($coach)->get(
            route('enrollments.show', $enrollment)
        );

        $response->assertOk();
        $response->assertSee($note->body);
    }

    public function test_unassigned_coach_cannot_view_note(): void
    {
        $coach = User::factory()->coach()->create();
        $enrollment = Enrollment::factory()->create();

        $note = EnrollmentNote::factory()->create([
            'enrollment_id' => $enrollment->id,
        ]);

        $response = $this->actingAs($coach)->get(
            route('enrollments.show', $enrollment)
        );

        $response->assertForbidden();
    }

    public function test_student_cannot_view_note_on_own_enrollment(): void
    {
        $student = User::factory()->student()->create();

        $enrollment = Enrollment::factory()->create([
            'user_id' => $student->id,
        ]);

        $note = EnrollmentNote::factory()->create([
            'enrollment_id' => $enrollment->id,
        ]);

        $response = $this->actingAs($student)->get(
            route('enrollments.show', $enrollment)
        );

        $response->assertOk();
        $response->assertDontSee($note->body);
    }

    /**
     * @return array{0: User, 1: Enrollment}
     */
    private function createEnrollmentWithAssignedCoach(): array
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

        return [$coach, $enrollment];
    }
}
