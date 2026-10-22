<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\StripeService;
use App\UseCases\MeetingQuota\HandleStripeWebhookAction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        StripeService $stripeService,
        HandleStripeWebhookAction $action,
    ): Response {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (! is_string($signature)) {
            return response(status: 400);
        }

        try {
            $event = $stripeService->constructWebhookEvent(
                payload: $payload,
                signature: $signature,
            );
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            report($e);

            return response(status: 400);
        }

        $action($event);

        return response(status: 200);
    }
}
