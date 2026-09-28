<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.show', $plan));

        $response->assertOk();
        $response->assertViewIs('meeting-pack.management.show');
        $response->assertViewHas('plan', $plan);
    }

    public function test_student_cannot_view_meeting_pack(): void
    {
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->create();

        $response = $this->actingAs($student)->get(route('admin.meeting-packs.show', $plan));

        $response->assertForbidden();
    }

    public function test_coach_cannot_view_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->create();

        $response = $this->actingAs($coach)->get(route('admin.meeting-packs.show', $plan));

        $response->assertForbidden();
    }
}
