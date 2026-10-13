<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Enums\MeetingReminderWindow;
use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\MeetingReminderDelivery;
use App\Notifications\MeetingReminderNotification;
use App\Services\NotificationEligibilityService;
use Throwable;

final class SendMeetingReminderAction
{
    public function __construct(
        private readonly NotificationEligibilityService $notificationEligibilityService,
    ) {}

    public function __invoke(
        Meeting $meeting,
        MeetingReminderWindow $window,
    ): void {
        if ($meeting->status !== MeetingStatus::Reserved) {
            return;
        }

        $recipients = [
            $meeting->student,
            $meeting->coach,
        ];

        foreach ($recipients as $recipient) {
            if (! $this->notificationEligibilityService->canReceive($recipient)) {
                continue;
            }

            $delivery = MeetingReminderDelivery::firstOrCreate(
                [
                    'meeting_id' => $meeting->id,
                    'user_id' => $recipient->id,
                    'window' => $window->value,
                ],
                [
                    'sent_at' => null,
                ],
            );

            if (! $delivery->wasRecentlyCreated) {
                continue;
            }

            try {
                $recipient->notify(
                    new MeetingReminderNotification($meeting, $window)
                );

                $delivery->update([
                    'sent_at' => now(),
                ]);
            } catch (Throwable $e) {
                $delivery->delete();

                throw $e;
            }
        }
    }
}
