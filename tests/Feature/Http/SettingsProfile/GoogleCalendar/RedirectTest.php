<?php

declare(strict_types=1);

namespace Tests\Feature\Http\SettingsProfile\GoogleCalendar;

use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_is_redirected(): void
    {
        $response = $this->get(route('settings.google-calendar.redirect'));

        $response->assertRedirect(route('login'));
    }

    public function test_coach_can_redirect_to_google_oauth(): void
    {
        $coach = User::factory()->coach()->create();

        $mock = Mockery::mock(GoogleCalendarService::class);

        $mock->shouldReceive('createAuthUrl')
            ->once()
            ->andReturn('https://accounts.google.com/o/oauth2/auth');

        $this->app->instance(GoogleCalendarService::class, $mock);

        $response = $this->actingAs($coach)->get(route('settings.google-calendar.redirect'));

        $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_student_is_forbidden_on_google_calendar_connect_endpoint(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('settings.google-calendar.redirect'));

        $response->assertForbidden();
    }

    public function test_admin_is_forbidden_on_google_calendar_connect_endpoint(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('settings.google-calendar.redirect'));

        $response->assertForbidden();
    }
}
