<?php

declare(strict_types=1);

namespace Tests\Feature\Http\SettingsProfile\GoogleCalendar;

use App\Models\GoogleCredential;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_is_redirected(): void
    {
        $response = $this->get(route('settings.google-calendar.callback'));

        $response->assertRedirect(route('login'));
    }

    public function test_coach_can_connect_google_calendar_with_valid_oauth_callback(): void
    {
        $coach = User::factory()->coach()->create();

        $credential = GoogleCredential::factory()
            ->for($coach, 'user')
            ->create();

        $token = [
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_in' => 3600,
            'created' => time(),
        ];

        $mock = Mockery::mock(GoogleCalendarService::class);

        $mock->shouldReceive('fetchAccessToken')
            ->once()
            ->with('auth-code')
            ->andReturn($token);

        $mock->shouldReceive('saveCredential')
            ->once()
            ->with(
                Mockery::on(
                    fn (User $user) => $user->is($coach)
                ),
                $token,
            )
            ->andReturn($credential);

        $this->app->instance(GoogleCalendarService::class, $mock);

        $response = $this->actingAs($coach)
            ->withSession([
                'google_calendar_oauth_state' => 'valid-state',
            ])
            ->get(route('settings.google-calendar.callback', [
                'state' => 'valid-state',
                'code' => 'auth-code',
            ]));

        $response->assertRedirect(route('settings.availability.index'));
        $response->assertSessionHas('success', 'Googleカレンダーと連携しました。');
    }

    public function test_rejects_oauth_callback_when_state_is_invalid(): void
    {
        $coach = User::factory()->coach()->create();

        $mock = Mockery::mock(GoogleCalendarService::class);

        $mock->shouldNotReceive('fetchAccessToken');
        $mock->shouldNotReceive('saveCredential');

        $this->app->instance(GoogleCalendarService::class, $mock);

        $response = $this->actingAs($coach)
            ->withSession([
                'google_calendar_oauth_state' => 'expected-state',
            ])
            ->get(route('settings.google-calendar.callback', [
                'state' => 'invalid-state',
                'code' => 'auth-code',
            ]));

        $response->assertRedirect(route('settings.availability.index'));

        $response->assertSessionHas('error', 'Googleカレンダーとの連携に失敗しました。');
    }
}
