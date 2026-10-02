<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Enums\PlanStatus;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_plan_as_draft(): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => '3ヶ月プラン',
            'description' => '3ヶ月間の受講プランです。',
            'duration_days' => 90,
            'default_meeting_quota' => 4,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($admin)->post(route('admin.plans.store'), $data);

        $plan = Plan::where('name', $data['name'])->firstOrFail();

        $response->assertRedirect(route('admin.plans.show', $plan));
        $response->assertSessionHas('success', 'プランを作成しました。');

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => $data['name'],
            'status' => PlanStatus::Draft->value,
        ]);
    }

    public function test_student_cannot_plan(): void
    {
        $student = User::factory()->student()->create();

        $data = [
            'name' => '3ヶ月プラン',
            'description' => '3ヶ月間の受講プランです。',
            'duration_days' => 90,
            'default_meeting_quota' => 4,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($student)->post(route('admin.plans.store'), $data);

        $response->assertForbidden();
    }

    public function test_coach_cannot_create_plan(): void
    {
        $coach = User::factory()->coach()->create();

        $data = [
            'name' => '3ヶ月プラン',
            'description' => '3ヶ月間の受講プランです。',
            'duration_days' => 90,
            'default_meeting_quota' => 4,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($coach)->post(route('admin.plans.store'), $data);

        $response->assertForbidden();
    }
}
