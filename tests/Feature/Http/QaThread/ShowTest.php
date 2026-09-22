<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_thread_for_published_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $response = $this->actingAs($student)->get(route('qa-board.show', $thread));

        $response->assertOk();
        $response->assertViewIs('qa-thread.show');
        $response->assertSee($thread->title);
    }

    public function test_student_cannot_view_thread_for_draft_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->draft()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $response = $this->actingAs($student)->get(route('qa-board.show', $thread));

        $response->assertForbidden();
    }

    public function test_coach_can_view_thread_for_assigned_published_certification(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $admin = User::factory()->admin()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $response = $this->actingAs($coach)->get(route('qa-board.show', $thread));

        $response->assertOk();
        $response->assertSee($thread->title);
    }

    public function test_coach_cannot_view_thread_for_unassigned_certification(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $response = $this->actingAs($coach)->get(route('qa-board.show', $thread));

        $response->assertForbidden();
    }

    public function test_admin_can_view_thread_for_draft_certification(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();

        $certification = Certification::factory()->draft()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $response = $this->actingAs($admin)->get(route('admin.qa-board.show', $thread));

        $response->assertOk();
        $response->assertSee($thread->title);
    }
}
