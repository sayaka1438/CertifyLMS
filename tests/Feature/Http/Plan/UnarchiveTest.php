<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnarchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_unarchives_archived_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->archived()->create();

        $response = $this->actingAs($admin)->post(route('admin.plans.unarchive', $plan));

        $response->assertRedirect(route('admin.plans.show', $plan));
        $response->assertSessionHas('success', 'プランを下書きに戻しました。');

        $this->assertSame('draft', $plan->fresh()->status->value);
    }

    public function test_cannot_unarchive_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.plans.unarchive', $plan));

        $response->assertStatus(409);
        $response->assertJsonPath('message', 'アーカイブ済みのプランのみ下書きに戻せます。');
    }

    public function test_cannot_unarchive_published(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.plans.unarchive', $plan));

        $response->assertStatus(409);
    }

    public function test_student_cannot_unarchive(): void
    {
        $student = User::factory()->student()->create();
        $plan = Plan::factory()->create();

        $response = $this->actingAs($student)->post(route('admin.plans.unarchive', $plan));

        $response->assertForbidden();
    }

    public function test_coach_cannot_unarchive(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = Plan::factory()->create();

        $response = $this->actingAs($coach)->post(route('admin.plans.unarchive', $plan));

        $response->assertForbidden();
    }
}
