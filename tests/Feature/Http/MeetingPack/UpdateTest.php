<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();

        $plan = MeetingPack::factory()->create([
            'name' => '更新前パック',
        ]);

        $data = [
            'name' => '変更後パック',
            'description' => '変更後のパックです。',
            'meeting_count' => 5,
            'price' => 15000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 10,
        ];

        $response = $this->actingAs($admin)->patch(route('admin.meeting-packs.update', $plan), $data);

        $response->assertRedirect(route('admin.meeting-packs.show', $plan));
        $response->assertSessionHas('success', '面談パックを更新しました。');

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'name' => $data['name'],
        ]);
    }

    public function test_status_cannot_be_changed_by_update(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create();

        $data = [
            'name' => $plan->name,
            'description' => $plan->description,
            'meeting_count' => $plan->meeting_count,
            'price' => $plan->price,
            'stripe_price_id' => $plan->stripe_price_id,
            'sort_order' => $plan->sort_order,
            'status' => 'draft',
        ];

        $this->actingAs($admin)->patch(route('admin.meeting-packs.update', $plan), $data);

        $this->assertSame('published', $plan->fresh()->status->value);
    }

    public function test_student_cannot_update_meeting_pack(): void
    {
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->create();

        $data = [
            'name' => '変更後パック',
            'description' => '変更後のパックです。',
            'meeting_count' => 5,
            'price' => 15000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 10,
        ];

        $response = $this->actingAs($student)->patch(route('admin.meeting-packs.update', $plan), $data);

        $response->assertForbidden();
    }

    public function test_coach_cannot_update_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->create();

        $data = [
            'name' => '変更後パック',
            'description' => '変更後のパックです。',
            'meeting_count' => 5,
            'price' => 15000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 10,
        ];

        $response = $this->actingAs($coach)->patch(route('admin.meeting-packs.update', $plan), $data);

        $response->assertForbidden();
    }
}
