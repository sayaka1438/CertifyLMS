<?php

declare(strict_types=1);

namespace App\UseCases\MeetingQuota;

use App\Models\Payment;
use Stripe\Checkout\Session;
use Stripe\Event;

final class HandleStripeWebhookAction
{
    public function __construct(
        private readonly CompletePaymentAction $completePaymentAction,
    ) {}

    public function __invoke(Event $event): void
    {
        if ($event->type !== 'checkout.session.completed') {
            return;
        }

        $session = $event->data->object;

        if (! $session instanceof Session) {
            return;
        }

        if ($session->payment_status !== 'paid') {
            return;
        }

        $paymentId = $session->metadata?->payment_id;

        if (! is_string($paymentId) || $paymentId === '') {
            return;
        }

        $payment = Payment::query()->find($paymentId);

        if ($payment === null) {
            return;
        }

        if ($payment->stripe_checkout_session_id === null) {
            throw new \RuntimeException(
                'Stripe Checkout Session ID has not been saved yet.'
            );
        }

        if ($payment->stripe_checkout_session_id !== $session->id) {
            return;
        }

        if ($session->currency !== 'jpy') {
            return;
        }

        if ($session->amount_total !== $payment->amount) {
            return;
        }

        $stripePaymentIntentId = $session->payment_intent;

        if (! is_string($stripePaymentIntentId) || $stripePaymentIntentId === '') {
            return;
        }

        ($this->completePaymentAction)(
            $payment,
            $stripePaymentIntentId,
        );
    }
}
