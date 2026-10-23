<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\GoogleCredential;
use App\Models\Meeting;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\FreeBusyCalendar;
use Google\Service\Calendar\FreeBusyRequest;
use Google\Service\Calendar\FreeBusyResponse;
use Google\Service\Calendar\Resource\Events;
use Google\Service\Calendar\Resource\Freebusy;
use Google\Service\Calendar\TimePeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('external-api')]
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

    public function test_busy_periods_returns_busy_periods_from_google_calendar(): void
    {
        $coach = User::factory()->coach()->create();

        $credential = GoogleCredential::factory()
            ->for($coach, 'user')
            ->create([
                'access_token' => 'access-token',
                'calendar_id' => 'primary',
                'expires_at' => now()->addHour(),
            ]);

        $client = \Mockery::mock(Client::class);

        $client->shouldReceive('setAccessToken')
            ->once()
            ->with([
                'access_token' => 'access-token',
            ]);

        $period = new TimePeriod;
        $period->setStart('2026-10-24T10:00:00+09:00');
        $period->setEnd('2026-10-24T11:00:00+09:00');

        $calendarBusy = new FreeBusyCalendar;
        $calendarBusy->setBusy([$period]);

        $response = new FreeBusyResponse;
        $response->setCalendars([
            'primary' => $calendarBusy,
        ]);

        $freebusy = \Mockery::mock(
            Freebusy::class
        );

        $freebusy->shouldReceive('query')
            ->once()
            ->with(\Mockery::on(
                function (FreeBusyRequest $request): bool {
                    $items = $request->getItems();

                    return $request->getTimeMin() === '2026-10-24T09:00:00+09:00'
                        && $request->getTimeMax() === '2026-10-24T12:00:00+09:00'
                        && count($items) === 1
                        && $items[0]->getId() === 'primary';
                }
            ))
            ->andReturn($response);

        $calendar = \Mockery::mock(Calendar::class);
        $calendar->freebusy = $freebusy;

        $service = \Mockery::mock(GoogleCalendarService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createClient')
            ->once()
            ->andReturn($client);

        $service->shouldReceive('createCalendar')
            ->once()
            ->with($client)
            ->andReturn($calendar);

        $result = $service->busyPeriods(
            $credential,
            Carbon::parse('2026-10-24 09:00:00', 'Asia/Tokyo'),
            Carbon::parse('2026-10-24 12:00:00', 'Asia/Tokyo'),
        );

        $this->assertCount(1, $result);
        $this->assertTrue(
            $result[0]['start']->equalTo(
                Carbon::parse('2026-10-24 10:00:00', 'Asia/Tokyo')
            )
        );
        $this->assertTrue(
            $result[0]['end']->equalTo(
                Carbon::parse('2026-10-24 11:00:00', 'Asia/Tokyo')
            )
        );
    }

    public function test_create_event_creates_google_calendar_event(): void
    {
        $student = User::factory()->student()->create([
            'name' => 'テスト受講生',
        ]);

        $coach = User::factory()->coach()->create();

        $certification = Certification::factory()->published()->create([
            'name' => 'テスト資格',
        ]);

        $enrollment = Enrollment::factory()
            ->for($student, 'user')
            ->for($certification)
            ->learning()
            ->create();

        $meeting = Meeting::factory()
            ->forEnrollment($enrollment)
            ->forCoach($coach)
            ->create([
                'scheduled_at' => Carbon::parse(
                    '2026-10-24 10:00:00',
                    'Asia/Tokyo'
                ),
                'topic' => '学習方法について相談したい',
                'meeting_url_snapshot' => 'https://meet.example.com/test-room',
            ]);

        $credential = GoogleCredential::factory()
            ->for($coach, 'user')
            ->create([
                'access_token' => 'access-token',
                'calendar_id' => 'primary',
                'expires_at' => now()->addHour(),
            ]);

        $client = \Mockery::mock(Client::class);

        $client->shouldReceive('setAccessToken')
            ->once()
            ->with([
                'access_token' => 'access-token',
            ]);

        $createdEvent = new Event;
        $createdEvent->setId('google-event-123');

        $events = \Mockery::mock(Events::class);

        $events->shouldReceive('insert')
            ->once()
            ->with(
                'primary',
                \Mockery::on(
                    function (Event $event): bool {
                        return $event->getSummary()
                            === '【CertifyLMS】テスト受講生（テスト資格）'
                            && $event->getDescription()
                            === "受講生：テスト受講生\n"
                            ."相談内容：学習方法について相談したい\n"
                            .'面談URL：https://meet.example.com/test-room'
                            && $event->getLocation()
                            === 'https://meet.example.com/test-room'
                            && $event->getStart()->getDateTime()
                            === '2026-10-24T10:00:00+09:00'
                            && $event->getEnd()->getDateTime()
                            === '2026-10-24T11:00:00+09:00';
                    }
                ),
            )
            ->andReturn($createdEvent);

        $calendar = \Mockery::mock(Calendar::class);
        $calendar->events = $events;

        $service = \Mockery::mock(GoogleCalendarService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createClient')
            ->once()
            ->andReturn($client);

        $service->shouldReceive('createCalendar')
            ->once()
            ->with($client)
            ->andReturn($calendar);

        $eventId = $service->createEvent(
            $credential,
            $meeting,
        );

        $this->assertSame('google-event-123', $eventId);
    }

    public function test_delete_event_deletes_google_calendar_event(): void
    {
        $coach = User::factory()->coach()->create();

        $credential = GoogleCredential::factory()
            ->for($coach, 'user')
            ->create([
                'access_token' => 'access-token',
                'calendar_id' => 'primary',
                'expires_at' => now()->addHour(),
            ]);

        $client = \Mockery::mock(Client::class);

        $client->shouldReceive('setAccessToken')
            ->once()
            ->with([
                'access_token' => 'access-token',
            ]);

        $events = \Mockery::mock(Events::class);

        $events->shouldReceive('delete')
            ->once()
            ->with(
                'primary',
                'google-event-123',
            );

        $calendar = \Mockery::mock(Calendar::class);
        $calendar->events = $events;

        $service = \Mockery::mock(GoogleCalendarService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createClient')
            ->once()
            ->andReturn($client);

        $service->shouldReceive('createCalendar')
            ->once()
            ->with($client)
            ->andReturn($calendar);

        $service->deleteEvent(
            $credential,
            'google-event-123',
        );
    }

    public function test_busy_periods_refreshes_expired_access_token_before_request(): void
    {
        $coach = User::factory()->coach()->create();

        $credential = GoogleCredential::factory()
            ->for($coach, 'user')
            ->create([
                'access_token' => 'expired-access-token',
                'refresh_token' => 'refresh-token',
                'calendar_id' => 'primary',
                'expires_at' => now()->subMinute(),
            ]);

        $client = \Mockery::mock(Client::class);

        $client->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->once()
            ->with('refresh-token')
            ->andReturn([
                'access_token' => 'refreshed-access-token',
                'expires_in' => 3600,
                'created' => now()->timestamp,
            ]);

        $client->shouldReceive('setAccessToken')
            ->once()
            ->with([
                'access_token' => 'refreshed-access-token',
            ]);

        $response = new FreeBusyResponse;
        $response->setCalendars([]);

        $freebusy = \Mockery::mock(Freebusy::class);

        $freebusy->shouldReceive('query')
            ->once()
            ->with(\Mockery::type(FreeBusyRequest::class))
            ->andReturn($response);

        $calendar = \Mockery::mock(Calendar::class);
        $calendar->freebusy = $freebusy;

        $service = \Mockery::mock(GoogleCalendarService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createClient')
            ->once()
            ->andReturn($client);

        $service->shouldReceive('createCalendar')
            ->once()
            ->with($client)
            ->andReturn($calendar);

        $result = $service->busyPeriods(
            $credential,
            Carbon::parse('2026-10-24 09:00:00', 'Asia/Tokyo'),
            Carbon::parse('2026-10-24 12:00:00', 'Asia/Tokyo'),
        );

        $this->assertSame([], $result);

        $credential->refresh();

        $this->assertSame(
            'refreshed-access-token',
            $credential->access_token,
        );

        $this->assertSame(
            'refresh-token',
            $credential->refresh_token,
        );

        $this->assertTrue(
            $credential->expires_at->isFuture(),
        );
    }

    public function test_busy_periods_throws_exception_when_access_token_refresh_fails(): void
    {
        $coach = User::factory()->coach()->create();

        $credential = GoogleCredential::factory()
            ->for($coach, 'user')
            ->create([
                'access_token' => 'expired-access-token',
                'refresh_token' => 'refresh-token',
                'calendar_id' => 'primary',
                'expires_at' => now()->subMinute(),
            ]);

        $client = \Mockery::mock(Client::class);

        $client->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->once()
            ->with('refresh-token')
            ->andReturn([
                'error' => 'invalid_grant',
                'error_description' => 'Token has been expired or revoked.',
            ]);

        $service = \Mockery::mock(GoogleCalendarService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createClient')
            ->once()
            ->andReturn($client);

        $service->shouldNotReceive('createCalendar');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Token has been expired or revoked.'
        );

        $service->busyPeriods(
            $credential,
            Carbon::parse('2026-10-24 09:00:00', 'Asia/Tokyo'),
            Carbon::parse('2026-10-24 12:00:00', 'Asia/Tokyo'),
        );
    }
}
