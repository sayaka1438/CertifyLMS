<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Announcement;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_announcement(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.announcements.show', $announcement));

        $response->assertOk();
        $response->assertViewIs('announcement.management.show');
        $response->assertViewHas('announcement', $announcement);
    }

    public function test_student_cannot_view_announcement(): void
    {
        $student = User::factory()->student()->create();
        $announcement = Announcement::factory()->create();

        $response = $this->actingAs($student)->get(route('admin.announcements.show', $announcement));

        $response->assertForbidden();
    }

    public function test_coach_cannot_view_announcement(): void
    {
        $coach = User::factory()->coach()->create();
        $announcement = Announcement::factory()->create();

        $response = $this->actingAs($coach)->get(route('admin.announcements.show', $announcement));

        $response->assertForbidden();
    }
}
