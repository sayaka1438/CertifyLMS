<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_qa_board_index(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $response = $this->actingAs($student)->get(route('qa-board.index'));

        $response->assertOk();
        $response->assertViewIs('qa-thread.index');
        $response->assertSee($thread->title);
    }

    public function test_student_cannot_see_threads_for_draft_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $publishedCertification = Certification::factory()->published()->create();
        $draftCertification = Certification::factory()->draft()->create();

        $publishedThread = QaThread::factory()
            ->for($publishedCertification)
            ->create();
        $draftThread = QaThread::factory()
            ->for($draftCertification)
            ->create();

        $response = $this->actingAs($student)->get(route('qa-board.index'));

        $response->assertSee($publishedThread->title);
        $response->assertDontSee($draftThread->title);
    }

    public function test_coach_can_only_see_threads_for_assigned_published_certifications(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $admin = User::factory()->admin()->inProgress()->create();

        $assignedCertification = Certification::factory()->published()->create();
        $unassignedCertification = Certification::factory()->published()->create();

        $assignedCertification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignedThread = QaThread::factory()
            ->for($assignedCertification)
            ->create();
        $unassignedThread = QaThread::factory()
            ->for($unassignedCertification)
            ->create();

        $response = $this->actingAs($coach)->get(route('qa-board.index'));

        $response->assertSee($assignedThread->title);
        $response->assertDontSee($unassignedThread->title);
    }

    public function test_admin_can_see_threads_for_all_certification_statuses(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();

        $publishedCertification = Certification::factory()->published()->create();
        $draftCertification = Certification::factory()->draft()->create();

        $publishedThread = QaThread::factory()
            ->for($publishedCertification)
            ->create();
        $draftThread = QaThread::factory()
            ->for($draftCertification)
            ->create();

        $response = $this->actingAs($admin)->get(route('admin.qa-board.index'));

        $response->assertSee($publishedThread->title);
        $response->assertSee($draftThread->title);
    }
}
