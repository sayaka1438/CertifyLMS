<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingQuota;

use App\Services\StripeService;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Tests\TestCase;

class StripeWebhookControllerTest extends TestCase
{
    public function test_webhook_returns_bad_request_when_stripe_signature_is_missing(): void
    {
        $response = $this->postJson(route('webhooks.stripe'), [
            'type' => 'checkout.session.completed',
        ]);

        $response->assertBadRequest();
    }

    public function test_webhook_returns_bad_request_when_stripe_signature_is_invalid(): void
    {
        $stripeService = $this->mock(StripeService::class);

        $stripeService
            ->shouldReceive('constructWebhookEvent')
            ->once()
            ->andThrow(new SignatureVerificationException('Invalid signature'));

        $response = $this
            ->withHeader('Stripe-Signature', 'invalid_signature')
            ->postJson(route('webhooks.stripe'), [
                'type' => 'checkout.session.completed',
            ]);

        $response->assertBadRequest();
    }

    public function test_webhook_returns_ok_for_valid_event(): void
    {
        $event = Event::constructFrom([
            'id' => 'evt_test',
            'type' => 'customer.created',
            'data' => [
                'object' => [
                    'id' => 'cus_test',
                    'object' => 'customer',
                ],
            ],
        ]);

        $stripeService = $this->mock(StripeService::class);

        $stripeService
            ->shouldReceive('constructWebhookEvent')
            ->once()
            ->andReturn($event);

        $response = $this
            ->withHeader('Stripe-Signature', 'valid_signature')
            ->postJson(route('webhooks.stripe'));

        $response->assertOk();
    }
}
