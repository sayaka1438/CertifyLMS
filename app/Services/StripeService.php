<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeService
{
    public function createCheckoutSession(Payment $payment): Session
    {
        $client = $this->createClient();

        $meetingPack = $payment->meetingPack;

        if ($meetingPack === null) {
            throw new \RuntimeException('Meeting pack is missing.');
        }

        return $client->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'jpy',
                        'unit_amount' => $payment->amount,
                        'product_data' => [
                            'name' => $meetingPack->name,
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'metadata' => [
                'payment_id' => $payment->id,
            ],
            'success_url' => route('meeting-quota.success', [
                'payment_id' => $payment->id,
            ]),
            'cancel_url' => route('meeting-quota.checkout.select', [
                'canceled' => 1,
            ]),
        ]);
    }

    public function constructWebhookEvent(
        string $payload,
        string $signature,
    ): Event {
        return Webhook::constructEvent(
            $payload,
            $signature,
            (string) config('services.stripe.webhook_secret'),
        );
    }

    private function createClient(): StripeClient
    {
        return new StripeClient(
            (string) config('services.stripe.secret')
        );
    }
}
