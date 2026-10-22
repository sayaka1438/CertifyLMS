<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingQuota;

use App\Enums\PaymentStatus;
use App\Models\MeetingPack;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripeService;
use App\UseCases\MeetingQuota\CreateCheckoutAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Stripe\Checkout\Session;
use Tests\TestCase;

class CreateCheckoutActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_payment_with_meeting_pack_snapshot_and_saves_checkout_session_id(): void
    {
        $student = User::factory()->student()->create();

        $meetingPack = MeetingPack::factory()->published()->create([
            'meeting_count' => 5,
            'price' => 12000,
        ]);

        $session = new Session('cs_test_checkout');
        $session->url = 'https://checkout.stripe.com/test';

        $stripeService = $this->mock(StripeService::class);
        $stripeService
            ->shouldReceive('createCheckoutSession')
            ->once()
            ->withArgs(
                fn (Payment $payment) => $payment->user_id === $student->id
                    && $payment->meeting_pack_id === $meetingPack->id
                    && $payment->quantity === 5
                    && $payment->amount === 12000
                    && $payment->status === PaymentStatus::Pending
            )
            ->andReturn($session);

        $result = app(CreateCheckoutAction::class)(
            $student,
            $meetingPack,
        );

        $payment = Payment::query()->firstOrFail();

        $this->assertSame($session, $result);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'user_id' => $student->id,
            'meeting_pack_id' => $meetingPack->id,
            'quantity' => 5,
            'amount' => 12000,
            'status' => PaymentStatus::Pending->value,
            'stripe_checkout_session_id' => 'cs_test_checkout',
        ]);
    }

    public function test_uses_snapshot_values_even_if_meeting_pack_is_changed_after_checkout_creation(): void
    {
        $student = User::factory()->student()->create();

        $meetingPack = MeetingPack::factory()->published()->create([
            'meeting_count' => 5,
            'price' => 12000,
        ]);

        $session = new Session('cs_test_snapshot');
        $session->url = 'https://checkout.stripe.com/test';

        $stripeService = $this->mock(StripeService::class);
        $stripeService
            ->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn($session);

        app(CreateCheckoutAction::class)(
            $student,
            $meetingPack,
        );

        $meetingPack->update([
            'meeting_count' => 10,
            'price' => 21000,
        ]);

        $payment = Payment::query()->firstOrFail();

        $this->assertSame(5, $payment->quantity);
        $this->assertSame(12000, $payment->amount);
    }

    public function test_marks_payment_as_failed_when_stripe_checkout_creation_fails(): void
    {
        $student = User::factory()->student()->create();
        $meetingPack = MeetingPack::factory()->published()->create();

        $stripeService = $this->mock(StripeService::class);
        $stripeService
            ->shouldReceive('createCheckoutSession')
            ->once()
            ->andThrow(new RuntimeException('Stripe error'));

        try {
            app(CreateCheckoutAction::class)(
                $student,
                $meetingPack,
            );

            $this->fail('RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Stripe error', $e->getMessage());
        }

        $payment = Payment::query()->firstOrFail();

        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertNull($payment->stripe_checkout_session_id);
    }

    public function test_marks_payment_as_failed_when_checkout_session_url_is_missing(): void
    {
        $student = User::factory()->student()->create();
        $meetingPack = MeetingPack::factory()->published()->create();

        $session = new Session('cs_test_missing_url');
        $session->url = null;

        $stripeService = $this->mock(StripeService::class);
        $stripeService
            ->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn($session);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stripe Checkout URL is missing.');

        try {
            app(CreateCheckoutAction::class)(
                $student,
                $meetingPack,
            );
        } finally {
            $payment = Payment::query()->firstOrFail();

            $this->assertSame(PaymentStatus::Failed, $payment->status);
            $this->assertNull($payment->stripe_checkout_session_id);
        }
    }
}
