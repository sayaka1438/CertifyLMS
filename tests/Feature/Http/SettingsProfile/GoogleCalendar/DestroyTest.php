<?php

declare(strict_types=1);

namespace Tests\Feature\Http\SettingsProfile\GoogleCalendar;

use App\Models\GoogleCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_is_redirected(): void
    {
        $response = $this->delete(route('settings.google-calendar.destroy'));

        $response->assertRedirect(route('login'));
    }

    public function test_coach_can_disconnect_google_calendar(): void
    {
        $coach = User::factory()->coach()->create();

        $credential = GoogleCredential::factory()
            ->for($coach, 'user')
            ->create();

        $response = $this->actingAs($coach)->delete(route('settings.google-calendar.destroy'));

        $response->assertRedirect(route('settings.availability.index'));
        $response->assertSessionHas('success', 'Googleカレンダーとの連携を解除しました。');

        $this->assertDatabaseMissing('google_credentials', [
            'id' => $credential->id,
        ]);
    }

    public function test_student_is_forbidden_on_google_calendar_disconnect_endpoint(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->delete(route('settings.google-calendar.destroy'));

        $response->assertForbidden();
    }

    public function test_admin_is_forbidden_on_google_calendar_disconnect_endpoint(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('settings.google-calendar.destroy'));

        $response->assertForbidden();
    }
}
