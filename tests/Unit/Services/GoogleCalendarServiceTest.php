<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\GoogleCredential;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GoogleCalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_credential_creates_google_credential(): void
    {
        Carbon::setTestNow('2026-10-17 10:00:00');

        $coach = User::factory()->coach()->create();

        $token = [
            'access_token' => 'new-access-token',
            'refresh_token' => 'new-refresh-token',
            'expires_in' => 3600,
            'created' => now()->timestamp,
        ];

        $credential = app(GoogleCalendarService::class)
            ->saveCredential($coach, $token);

        $this->assertSame($coach->id, $credential->user_id);
        $this->assertSame('new-access-token', $credential->access_token);
        $this->assertSame('new-refresh-token', $credential->refresh_token);
        $this->assertSame('primary', $credential->calendar_id);
        $this->assertTrue(
            $credential->expires_at->equalTo(now()->addHour())
        );
        $this->assertTrue(
            $credential->connected_at->equalTo(now())
        );

        Carbon::setTestNow();
    }

    public function test_save_credential_keeps_existing_refresh_token_when_new_token_does_not_include_one(): void
    {
        Carbon::setTestNow('2026-10-17 10:00:00');

        $coach = User::factory()->coach()->create();

        GoogleCredential::factory()
            ->for($coach, 'user')
            ->create([
                'access_token' => 'old-access-token',
                'refresh_token' => 'existing-refresh-token',
            ]);

        $token = [
            'access_token' => 'new-access-token',
            'expires_in' => 3600,
            'created' => now()->timestamp,
        ];

        $credential = app(GoogleCalendarService::class)
            ->saveCredential($coach, $token);

        $this->assertSame('new-access-token', $credential->access_token);
        $this->assertSame(
            'existing-refresh-token',
            $credential->refresh_token
        );
        $this->assertSame('primary', $credential->calendar_id);
        $this->assertTrue(
            $credential->expires_at->equalTo(now()->addHour())
        );

        Carbon::setTestNow();
    }
}
