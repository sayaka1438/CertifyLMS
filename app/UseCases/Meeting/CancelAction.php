<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Enums\MeetingStatus;
use App\Enums\UserRole;
use App\Exceptions\Mentoring\MeetingAlreadyStartedException;
use App\Exceptions\Mentoring\MeetingStatusTransitionException;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingCanceledNotification;
use App\Services\NotificationEligibilityService;
use App\UseCases\MeetingQuota\RefundQuotaAction;
use Illuminate\Support\Facades\DB;

final class CancelAction
{
    public function __construct(
        private readonly RefundQuotaAction $refundAction,
        private readonly NotificationEligibilityService $notificationEligibility,
    ) {}

    public function __invoke(
        Meeting $meeting,
        User $actor,
    ): Meeting {
        return DB::transaction(function () use ($meeting, $actor) {
            $locked = Meeting::query()
                ->whereKey($meeting->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || $locked->status !== MeetingStatus::Reserved) {
                throw MeetingStatusTransitionException::forCancel();
            }

            if ($locked->scheduled_at->lessThanOrEqualTo(now())) {
                throw new MeetingAlreadyStartedException;
            }

            $locked->update([
                'status' => MeetingStatus::Canceled->value,
                'canceled_by_user_id' => $actor->id,
                'canceled_at' => now(),
            ]);

            ($this->refundAction)($locked->student, $locked->id);

            $canceledMeeting = $locked->fresh();

            if ($actor->role === UserRole::Student) {
                $recipient = $canceledMeeting->coach;
            } else {
                $recipient = $canceledMeeting->student;
            }

            if ($this->notificationEligibility->canReceive($recipient)) {
                $recipient->notify(
                    new MeetingCanceledNotification($canceledMeeting),
                );
            }

            return $canceledMeeting;
        });
    }
}
