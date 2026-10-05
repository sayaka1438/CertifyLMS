<?php

declare(strict_types=1);

namespace App\UseCases\QaReply;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use App\Notifications\QaReplyReceivedNotification;
use App\Services\NotificationEligibilityService;

final class StoreAction
{
    public function __construct(
        private readonly NotificationEligibilityService $notificationEligibility,
    ) {}

    /**
     * @param array{
     *     body: string,
     * } $validated
     */
    public function __invoke(QaThread $thread, User $user, array $validated): QaReply
    {
        $reply = $thread->replies()->create([
            'user_id' => $user->id,
            'body' => $validated['body'],
        ]);

        $recipient = $thread->user;

        if ($this->notificationEligibility->canReceive($recipient)) {
            $recipient->notify(
                new QaReplyReceivedNotification($thread),
            );
        }

        return $reply;
    }
}
