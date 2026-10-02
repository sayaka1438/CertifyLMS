<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_draft_plan_without_users(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create();

        $response = $this->actingAs($admin)->delete(route('admin.plans.destroy', $plan));

        $response->assertRedirect(route('admin.plans.index'));
        $response->assertSessionHas('success', 'プランを削除しました。');

        $this->assertDatabaseMissing('plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_cannot_delete_draft_plan_with_user(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create();

        User::factory()->student()->withPlan($plan)->create();

        $response = $this->actingAs($admin)->deleteJson(route('admin.plans.destroy', $plan));

        $response->assertStatus(409);
        $response->assertJsonPath('message', '下書きかつ受講者が紐づいていないプランのみ削除できます。');

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_cannot_delete_published_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();

        $response = $this->actingAs($admin)->deleteJson(route('admin.plans.destroy', $plan));

        $response->assertStatus(409);
        $response->assertJsonPath('message', '下書きかつ受講者が紐づいていないプランのみ削除できます。');

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_cannot_delete_archived_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->archived()->create();

        $response = $this->actingAs($admin)->deleteJson(route('admin.plans.destroy', $plan));

        $response->assertStatus(409);
        $response->assertJsonPath('message', '下書きかつ受講者が紐づいていないプランのみ削除できます。');

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_student_cannot_delete_plan(): void
    {
        $student = User::factory()->student()->create();
        $plan = Plan::factory()->create();

        $response = $this->actingAs($student)->delete(route('admin.plans.destroy', $plan));

        $response->assertForbidden();
    }

    public function test_coach_cannot_delete_plan(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = Plan::factory()->create();

        $response = $this->actingAs($coach)->delete(route('admin.plans.destroy', $plan));

        $response->assertForbidden();
    }
}
