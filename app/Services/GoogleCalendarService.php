<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoogleCredential;
use App\Models\Meeting;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\FreeBusyRequest;
use Google\Service\Calendar\FreeBusyRequestItem;

class GoogleCalendarService
{
    public function createAuthUrl(): string
    {
        $client = $this->createClient();

        $state = bin2hex(random_bytes(32));

        session()->put('google_calendar_oauth_state', $state);

        $client->setState($state);

        return $client->createAuthUrl();
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchAccessToken(string $code): array
    {
        $client = $this->createClient();

        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new \RuntimeException(
                (string) ($token['error_description'] ?? $token['error'])
            );
        }

        return $token;
    }

    /**
     * @param array<string, mixed> $token
     */
    public function saveCredential(User $user, array $token): GoogleCredential
    {
        $expiresIn = (int) ($token['expires_in'] ?? 3600);
        $created = (int) ($token['created'] ?? time());

        $existingCredential = $user->googleCredential;

        return GoogleCredential::query()->updateOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'access_token' => (string) $token['access_token'],
                'refresh_token' => $token['refresh_token']
                    ?? $existingCredential?->refresh_token,
                'expires_at' => Carbon::createFromTimestamp($created)
                    ->addSeconds($expiresIn),
                'calendar_id' => 'primary',
                'connected_at' => now(),
            ],
        );
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    public function busyPeriods(
        GoogleCredential $credential,
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        $client = $this->createAuthorizedClient($credential);
        $calendar = new Calendar($client);

        $item = new FreeBusyRequestItem;
        $item->setId($credential->calendar_id);

        $request = new FreeBusyRequest;
        $request->setTimeMin($start->toRfc3339String());
        $request->setTimeMax($end->toRfc3339String());
        $request->setItems([$item]);

        $response = $calendar->freebusy->query($request);

        $calendarBusy = $response
            ->getCalendars()[$credential->calendar_id]
            ?? null;

        if ($calendarBusy === null) {
            return [];
        }

        return collect($calendarBusy->getBusy())
            ->map(fn ($period) => [
                'start' => Carbon::parse($period->getStart()),
                'end' => Carbon::parse($period->getEnd()),
            ])
            ->all();
    }

    public function createEvent(
        GoogleCredential $credential,
        Meeting $meeting,
    ): string {
        $client = $this->createAuthorizedClient($credential);
        $calendar = new Calendar($client);

        $start = new EventDateTime;
        $start->setDateTime(
            $meeting->scheduled_at->toRfc3339String()
        );

        $end = new EventDateTime;
        $end->setDateTime(
            $meeting->scheduled_at->copy()->addHour()->toRfc3339String()
        );

        $studentName = $meeting->student->name;
        $certificationName = $meeting->enrollment->certification->name;

        $event = new Event;
        $event->setSummary("【CertifyLMS】{$studentName}（{$certificationName}）");
        $event->setDescription(
            "受講生：{$studentName}\n"
            ."相談内容：{$meeting->topic}\n"
            ."面談URL：{$meeting->meeting_url_snapshot}"
        );
        $event->setLocation($meeting->meeting_url_snapshot);
        $event->setStart($start);
        $event->setEnd($end);

        $createdEvent = $calendar->events->insert(
            $credential->calendar_id,
            $event,
        );

        $eventId = $createdEvent->getId();

        if ($eventId === null || $eventId === '') {
            throw new \RuntimeException(
                'Google Calendar event ID is missing.'
            );
        }

        return $eventId;
    }

    public function deleteEvent(
        GoogleCredential $credential,
        string $eventId,
    ): void {
        $client = $this->createAuthorizedClient($credential);
        $calendar = new Calendar($client);

        $calendar->events->delete(
            $credential->calendar_id,
            $eventId,
        );
    }

    private function createAuthorizedClient(
        GoogleCredential $credential
    ): Client {
        $client = $this->createClient();

        if (
            $credential->expires_at !== null
            && $credential->expires_at->isPast()
        ) {
            $this->refreshAccessToken($client, $credential);
        }

        $client->setAccessToken([
            'access_token' => $credential->access_token,
        ]);

        return $client;
    }

    private function refreshAccessToken(
        Client $client,
        GoogleCredential $credential
    ): void {
        if ($credential->refresh_token === null) {
            throw new \RuntimeException('Google refresh token is missing.');
        }

        $token = $client->fetchAccessTokenWithRefreshToken(
            $credential->refresh_token
        );

        if (isset($token['error'])) {
            throw new \RuntimeException(
                (string) ($token['error_description'] ?? $token['error'])
            );
        }

        $expiresIn = (int) ($token['expires_in'] ?? 3600);
        $created = (int) ($token['created'] ?? time());

        $credential->update([
            'access_token' => (string) $token['access_token'],
            'refresh_token' => $token['refresh_token']
                ?? $credential->refresh_token,
            'expires_at' => Carbon::createFromTimestamp($created)
                ->addSeconds($expiresIn),
        ]);

        $credential->refresh();
    }

    private function createClient(): Client
    {
        $client = new Client;

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri'));

        $client->setScopes([
            Calendar::CALENDAR,
        ]);

        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }
}
