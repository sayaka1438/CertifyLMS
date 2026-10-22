<?php

declare(strict_types=1);

namespace App\UseCases\MeetingQuota;

use App\Enums\PaymentStatus;
use App\Models\MeetingPack;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripeService;
use Stripe\Checkout\Session;

final class CreateCheckoutAction
{
    public function __construct(
        private readonly StripeService $stripeService,
    ) {}

    public function __invoke(
        User $student,
        MeetingPack $meetingPack,
    ): Session {
        $payment = Payment::create([
            'user_id' => $student->id,
            'meeting_pack_id' => $meetingPack->id,
            'quantity' => $meetingPack->meeting_count,
            'amount' => $meetingPack->price,
            'status' => PaymentStatus::Pending->value,
        ]);

        try {
            $session = $this->stripeService->createCheckoutSession($payment);

            if ($session->url === null || $session->url === '') {
                throw new \RuntimeException('Stripe Checkout URL is missing.');
            }
        } catch (\Throwable $e) {
            $payment->update([
                'status' => PaymentStatus::Failed->value,
            ]);

            throw $e;
        }

        $payment->update([
            'stripe_checkout_session_id' => $session->id,
        ]);

        return $session;
    }
}
