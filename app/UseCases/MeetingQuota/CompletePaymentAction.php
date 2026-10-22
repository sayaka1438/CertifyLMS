<?php

declare(strict_types=1);

namespace App\UseCases\MeetingQuota;

use App\Enums\MeetingQuotaTransactionType;
use App\Enums\PaymentStatus;
use App\Models\MeetingQuotaTransaction;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

final class CompletePaymentAction
{
    public function __invoke(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($lockedPayment->status === PaymentStatus::Completed) {
                return;
            }

            MeetingQuotaTransaction::create([
                'user_id' => $lockedPayment->user_id,
                'type' => MeetingQuotaTransactionType::Purchased->value,
                'amount' => $lockedPayment->quantity,
                'related_payment_id' => $lockedPayment->id,
                'occurred_at' => now(),
            ]);

            $lockedPayment->update([
                'status' => PaymentStatus::Completed->value,
                'completed_at' => now(),
            ]);
        });
    }
}
