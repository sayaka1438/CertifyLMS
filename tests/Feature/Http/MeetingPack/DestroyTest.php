<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_draft_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $response = $this->actingAs($admin)->delete(route('admin.meeting-packs.destroy', $plan));

        $response->assertRedirect(route('admin.meeting-packs.index'));
        $response->assertSessionHas('success', '面談パックを削除しました。');

        $this->assertDatabaseMissing('meeting_packs', [
            'id' => $plan->id,
        ]);
    }

    public function test_admin_can_delete_archived_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->archived()->create();

        $response = $this->actingAs($admin)->delete(route('admin.meeting-packs.destroy', $plan));

        $response->assertRedirect(route('admin.meeting-packs.index'));

        $this->assertDatabaseMissing('meeting_packs', [
            'id' => $plan->id,
        ]);
    }

    public function test_cannot_delete_published_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create();

        $response = $this->actingAs($admin)->deleteJson(route('admin.meeting-packs.destroy', $plan));

        $response->assertStatus(409);
        $response->assertJsonPath('message', '公開中の面談パックは削除できません。');

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
        ]);
    }

    public function test_student_cannot_delete_meeting_pack(): void
    {
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->create();

        $response = $this->actingAs($student)->delete(route('admin.meeting-packs.destroy', $plan));

        $response->assertForbidden();
    }

    public function test_coach_cannot_delete_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->create();

        $response = $this->actingAs($coach)->delete(route('admin.meeting-packs.destroy', $plan));

        $response->assertForbidden();
    }
}
