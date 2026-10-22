<?php

declare(strict_types=1);

namespace App\UseCases\MeetingQuota;

use App\Models\Payment;
use App\Models\User;

final class ShowPaymentAction
{
    public function __invoke(
        User $student,
        string $paymentId,
    ): Payment {
        return Payment::query()
            ->with('meetingPack')
            ->where('user_id', $student->id)
            ->findOrFail($paymentId);
    }
}
