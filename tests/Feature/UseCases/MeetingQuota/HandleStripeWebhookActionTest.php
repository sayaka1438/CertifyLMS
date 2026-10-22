<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingQuota;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\UseCases\MeetingQuota\HandleStripeWebhookAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Event;
use Tests\TestCase;

class HandleStripeWebhookActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_completes_payment_when_paid_checkout_session_completed_event_is_received(): void
    {
        $student = User::factory()->student()->create();

        $payment = Payment::factory()->pending()->create([
            'user_id' => $student->id,
            'quantity' => 5,
            'amount' => 12000,
            'stripe_checkout_session_id' => 'cs_test_completed',
        ]);

        $event = $this->createEvent(
            type: 'checkout.session.completed',
            sessionId: 'cs_test_completed',
            paymentId: $payment->id,
            paymentStatus: 'paid',
            currency: 'jpy',
            amountTotal: 12000,
        );

        app(HandleStripeWebhookAction::class)($event);

        $payment->refresh();

        $this->assertSame(PaymentStatus::Completed, $payment->status);
        $this->assertNotNull($payment->completed_at);

        $this->assertDatabaseHas('meeting_quota_transactions', [
            'user_id' => $student->id,
            'type' => 'purchased',
            'amount' => 5,
            'related_payment_id' => $payment->id,
        ]);
    }

    public function test_does_nothing_when_payment_status_is_not_paid(): void
    {
        $payment = Payment::factory()->pending()->create([
            'amount' => 12000,
            'stripe_checkout_session_id' => 'cs_test_unpaid',
        ]);

        $event = $this->createEvent(
            type: 'checkout.session.completed',
            sessionId: 'cs_test_unpaid',
            paymentId: $payment->id,
            paymentStatus: 'unpaid',
            currency: 'jpy',
            amountTotal: 12000,
        );

        app(HandleStripeWebhookAction::class)($event);

        $this->assertSame(
            PaymentStatus::Pending,
            $payment->fresh()->status,
        );

        $this->assertDatabaseMissing('meeting_quota_transactions', [
            'related_payment_id' => $payment->id,
        ]);
    }

    public function test_does_nothing_when_event_type_is_unexpected(): void
    {
        $payment = Payment::factory()->pending()->create([
            'amount' => 12000,
            'stripe_checkout_session_id' => 'cs_test_unexpected',
        ]);

        $event = $this->createEvent(
            type: 'checkout.session.expired',
            sessionId: 'cs_test_unexpected',
            paymentId: $payment->id,
            paymentStatus: 'unpaid',
            currency: 'jpy',
            amountTotal: 12000,
        );

        app(HandleStripeWebhookAction::class)($event);

        $this->assertSame(
            PaymentStatus::Pending,
            $payment->fresh()->status,
        );

        $this->assertDatabaseMissing('meeting_quota_transactions', [
            'related_payment_id' => $payment->id,
        ]);
    }

    public function test_does_nothing_when_checkout_session_id_does_not_match(): void
    {
        $payment = Payment::factory()->pending()->create([
            'amount' => 12000,
            'stripe_checkout_session_id' => 'cs_test_expected',
        ]);

        $event = $this->createEvent(
            type: 'checkout.session.completed',
            sessionId: 'cs_test_different',
            paymentId: $payment->id,
            paymentStatus: 'paid',
            currency: 'jpy',
            amountTotal: 12000,
        );

        app(HandleStripeWebhookAction::class)($event);

        $this->assertSame(
            PaymentStatus::Pending,
            $payment->fresh()->status,
        );

        $this->assertDatabaseMissing('meeting_quota_transactions', [
            'related_payment_id' => $payment->id,
        ]);
    }

    public function test_does_nothing_when_currency_does_not_match(): void
    {
        $payment = Payment::factory()->pending()->create([
            'amount' => 12000,
            'stripe_checkout_session_id' => 'cs_test_currency',
        ]);

        $event = $this->createEvent(
            type: 'checkout.session.completed',
            sessionId: 'cs_test_currency',
            paymentId: $payment->id,
            paymentStatus: 'paid',
            currency: 'usd',
            amountTotal: 12000,
        );

        app(HandleStripeWebhookAction::class)($event);

        $this->assertSame(
            PaymentStatus::Pending,
            $payment->fresh()->status,
        );

        $this->assertDatabaseMissing('meeting_quota_transactions', [
            'related_payment_id' => $payment->id,
        ]);
    }

    public function test_does_nothing_when_amount_does_not_match(): void
    {
        $payment = Payment::factory()->pending()->create([
            'amount' => 12000,
            'stripe_checkout_session_id' => 'cs_test_amount',
        ]);

        $event = $this->createEvent(
            type: 'checkout.session.completed',
            sessionId: 'cs_test_amount',
            paymentId: $payment->id,
            paymentStatus: 'paid',
            currency: 'jpy',
            amountTotal: 9999,
        );

        app(HandleStripeWebhookAction::class)($event);

        $this->assertSame(
            PaymentStatus::Pending,
            $payment->fresh()->status,
        );

        $this->assertDatabaseMissing('meeting_quota_transactions', [
            'related_payment_id' => $payment->id,
        ]);
    }

    private function createEvent(
        string $type,
        string $sessionId,
        string $paymentId,
        string $paymentStatus,
        string $currency,
        int $amountTotal,
    ): Event {
        return Event::constructFrom([
            'id' => 'evt_test',
            'object' => 'event',
            'type' => $type,
            'data' => [
                'object' => [
                    'id' => $sessionId,
                    'object' => 'checkout.session',
                    'payment_status' => $paymentStatus,
                    'currency' => $currency,
                    'amount_total' => $amountTotal,
                    'metadata' => [
                        'payment_id' => $paymentId,
                    ],
                ],
            ],
        ]);
    }
}
