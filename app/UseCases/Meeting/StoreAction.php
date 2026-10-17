<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Enums\MeetingStatus;
use App\Exceptions\MeetingQuota\InsufficientMeetingQuotaException;
use App\Exceptions\Mentoring\MeetingNoAvailableCoachException;
use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingReservedNotification;
use App\Services\CoachMeetingLoadService;
use App\Services\GoogleCalendarService;
use App\Services\MeetingAvailabilityService;
use App\Services\MeetingQuotaService;
use App\Services\NotificationEligibilityService;
use App\UseCases\MeetingQuota\ConsumeQuotaAction;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StoreAction
{
    public function __construct(
        private readonly MeetingAvailabilityService $availabilityService,
        private readonly CoachMeetingLoadService $coachLoadService,
        private readonly MeetingQuotaService $quotaService,
        private readonly ConsumeQuotaAction $consumeAction,
        private readonly NotificationEligibilityService $notificationEligibility,
        private readonly GoogleCalendarService $googleCalendarService,
    ) {}

    public function __invoke(
        Enrollment $enrollment,
        string $scheduledAt,
        string $topic,
    ): Meeting {
        $scheduledAt = Carbon::parse($scheduledAt);
        $student = $enrollment->user;

        return DB::transaction(function () use ($enrollment, $student, $scheduledAt, $topic) {
            if ($this->quotaService->remaining($student) < 1) {
                throw new InsufficientMeetingQuotaException;
            }

            $this->availabilityService->validateSlot(
                $enrollment->certification,
                $scheduledAt,
            );

            $candidates = $this->findAvailableCoaches(
                $enrollment->certification,
                $scheduledAt,
            );

            if ($candidates->isEmpty()) {
                throw new MeetingNoAvailableCoachException;
            }

            $coach = $this->coachLoadService->leastLoadedCoach($candidates);

            try {
                $meeting = Meeting::create([
                    'enrollment_id' => $enrollment->id,
                    'coach_id' => $coach->id,
                    'student_id' => $student->id,
                    'scheduled_at' => $scheduledAt,
                    'status' => MeetingStatus::Reserved->value,
                    'topic' => $topic,
                    'meeting_url_snapshot' => $coach->meeting_url,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                throw new MeetingNoAvailableCoachException($e);
            }

            $transaction = ($this->consumeAction)($student, $meeting->id);

            $meeting->update([
                'meeting_quota_transaction_id' => $transaction->id,
            ]);

            $meeting = $meeting->fresh();

            if ($this->notificationEligibility->canReceive($coach)) {
                $coach->notify(
                    new MeetingReservedNotification($meeting),
                );
            }

            if ($coach->googleCredential !== null) {
                try {
                    $eventId = $this->googleCalendarService->createEvent(
                        $coach->googleCredential,
                        $meeting,
                    );

                    $meeting->update([
                        'google_calendar_event_id' => $eventId,
                    ]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return $meeting;
        });
    }

    /**
     * @return Collection<int, User>
     */
    private function findAvailableCoaches(
        Certification $certification,
        Carbon $scheduledAt,
    ): Collection {
        $time = $scheduledAt->format('H:i:s');

        $candidates = $certification->coaches()
            ->with('googleCredential')
            ->whereHas('coachAvailabilities', function ($q) use ($scheduledAt, $time) {
                $q->where('day_of_week', $scheduledAt->dayOfWeek)
                    ->where('is_active', true)
                    ->where('start_time', '<=', $time)
                    ->where('end_time', '>', $time);
            })
            ->whereDoesntHave('meetingsAsCoach', function ($q) use ($scheduledAt) {
                $q->where('scheduled_at', $scheduledAt)
                    ->whereIn('status', [
                        MeetingStatus::Reserved->value,
                        MeetingStatus::Completed->value,
                    ]);
            })
            ->get();

        $slotEnd = $scheduledAt->copy()->addHour();

        return $candidates
            ->filter(function (User $coach) use ($scheduledAt, $slotEnd) {
                if ($coach->googleCredential === null) {
                    return true;
                }

                try {
                    $busyPeriods = $this->googleCalendarService->busyPeriods(
                        $coach->googleCredential,
                        $scheduledAt,
                        $slotEnd,
                    );

                    return $busyPeriods === [];
                } catch (\Throwable $e) {
                    report($e);

                    return true;
                }
            })
            ->values();
    }
}
