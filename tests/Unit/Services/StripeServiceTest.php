<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\StripeService;
use Illuminate\Support\Facades\Config;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Tests\TestCase;

class StripeServiceTest extends TestCase
{
    public function test_construct_webhook_event_returns_event_with_valid_signature(): void
    {
        $secret = 'whsec_test_secret';
        $timestamp = time();
        $payload = json_encode([
            'id' => 'evt_test',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test',
                    'object' => 'checkout.session',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        Config::set('services.stripe.webhook_secret', $secret);

        $signature = hash_hmac(
            'sha256',
            $timestamp.'.'.$payload,
            $secret,
        );

        $signatureHeader = "t={$timestamp},v1={$signature}";

        $service = new StripeService;

        $event = $service->constructWebhookEvent(
            $payload,
            $signatureHeader,
        );

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('evt_test', $event->id);
        $this->assertSame('checkout.session.completed', $event->type);
    }

    public function test_construct_webhook_event_throws_exception_with_invalid_signature(): void
    {
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret');

        $payload = json_encode([
            'id' => 'evt_test',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test',
                    'object' => 'checkout.session',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $service = new StripeService;

        $this->expectException(SignatureVerificationException::class);

        $service->constructWebhookEvent(
            $payload,
            't=1234567890,v1=invalid_signature',
        );
    }
}
