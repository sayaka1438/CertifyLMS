<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->create();

        $data = [
            'name' => '変更後プラン',
            'description' => '変更後のプランです。',
            'duration_days' => 90,
            'default_meeting_quota' => 4,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($admin)->put(route('admin.plans.update', $plan), $data);

        $response->assertRedirect(route('admin.plans.show', $plan));
        $response->assertSessionHas('success', 'プランを更新しました。');

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => $data['name'],
        ]);
    }

    public function test_status_cannot_be_changed_by_update(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();

        $data = [
            'name' => $plan->name,
            'description' => $plan->description,
            'duration_days' => $plan->duration_days,
            'default_meeting_quota' => $plan->default_meeting_quota,
            'sort_order' => $plan->sort_order,
            'status' => 'draft',
        ];

        $this->actingAs($admin)->put(route('admin.plans.update', $plan), $data);

        $this->assertSame('published', $plan->fresh()->status->value);
    }

    public function test_student_cannot_update_plan(): void
    {
        $student = User::factory()->student()->create();
        $plan = Plan::factory()->create();

        $data = [
            'name' => '変更後プラン',
            'description' => '変更後のプランです。',
            'duration_days' => 90,
            'default_meeting_quota' => 4,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($student)->put(route('admin.plans.update', $plan), $data);

        $response->assertForbidden();
    }

    public function test_coach_cannot_update_plan(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = Plan::factory()->create();

        $data = [
            'name' => '変更後プラン',
            'description' => '変更後のプランです。',
            'duration_days' => 90,
            'default_meeting_quota' => 4,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($coach)->put(route('admin.plans.update', $plan), $data);

        $response->assertForbidden();
    }
}
