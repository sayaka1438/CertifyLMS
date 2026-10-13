<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Announcement;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_announcement_create(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.announcements.create'));

        $response->assertOk();
        $response->assertViewIs('announcement.management.create');
        $response->assertViewHas('certifications');
        $response->assertViewHas('students');
    }

    public function test_student_cannot_access_announcement_create(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('admin.announcements.create'));

        $response->assertForbidden();
    }

    public function test_coach_cannot_access_announcement_create(): void
    {
        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)->get(route('admin.announcements.create'));

        $response->assertForbidden();
    }
}
