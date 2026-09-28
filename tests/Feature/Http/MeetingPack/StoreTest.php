<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_meeting_pack_as_draft(): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => '5回パック',
            'description' => '追加面談5回分のパックです。',
            'meeting_count' => 5,
            'price' => 15000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 10,
        ];

        $response = $this->actingAs($admin)->post(route('admin.meeting-packs.store'), $data);

        $plan = MeetingPack::where('name', $data['name'])->firstOrFail();

        $response->assertRedirect(route('admin.meeting-packs.show', $plan));
        $response->assertSessionHas('success', '面談パックを作成しました。');

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'name' => $data['name'],
            'status' => MeetingPackStatus::Draft->value,
        ]);
    }

    public function test_student_cannot_create_meeting_pack(): void
    {
        $student = User::factory()->student()->create();

        $data = [
            'name' => '5回パック',
            'description' => '追加面談5回分のパックです。',
            'meeting_count' => 5,
            'price' => 15000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 10,
        ];

        $response = $this->actingAs($student)->post(route('admin.meeting-packs.store'), $data);

        $response->assertForbidden();
    }

    public function test_coach_cannot_create_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();

        $data = [
            'name' => '5回パック',
            'description' => '追加面談5回分のパックです。',
            'meeting_count' => 5,
            'price' => 15000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 10,
        ];

        $response = $this->actingAs($coach)->post(route('admin.meeting-packs.store'), $data);

        $response->assertForbidden();
    }
}
