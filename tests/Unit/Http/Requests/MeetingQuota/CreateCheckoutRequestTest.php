<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\MeetingQuota;

use App\Models\MeetingPack;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Stripe\Checkout\Session;
use Tests\TestCase;

class CreateCheckoutRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_published_meeting_pack(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $meetingPack = MeetingPack::factory()->published()->create();

        $session = new Session('cs_test_checkout');
        $session->url = 'https://checkout.stripe.com/test';

        $stripeService = $this->mock(StripeService::class);

        $stripeService
            ->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn($session);

        $data = [
            'meeting_pack_id' => $meetingPack->id,
        ];

        $response = $this->actingAs($student)->post(route('meeting-quota.checkout.create'), $data);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $payload): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $response = $this->actingAs($student)->postJson(route('meeting-quota.checkout.create'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('meeting_pack_id');
    }

    public function test_validation_fails_with_draft_meeting_pack(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $draftPack = MeetingPack::factory()->draft()->create();

        $response = $this->actingAs($student)->postJson(route('meeting-quota.checkout.create'), [
            'meeting_pack_id' => $draftPack->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('meeting_pack_id');
    }

    public function test_validation_fails_with_archived_meeting_pack(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $archivedPack = MeetingPack::factory()->archived()->create();

        $response = $this->actingAs($student)->postJson(route('meeting-quota.checkout.create'), [
            'meeting_pack_id' => $archivedPack->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('meeting_pack_id');
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'meeting_pack_id 未指定で 422' => [
                [],
            ],
            'meeting_pack_id 不正な形式で 422' => [
                ['meeting_pack_id' => 'invalid-id'],
            ],
            'meeting_pack_id 存在しない ULID で 422' => [
                ['meeting_pack_id' => '01J00000000000000000000000'],
            ],
        ];
    }
}
