<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\SettingsProfile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateProfileRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_data(): void
    {
        $student = User::factory()->student()->create();

        $data = [
            'name' => '更新後の名前',
            'bio' => '更新後の自己紹介',
        ];

        $response = $this->actingAs($student)->patch(route('settings.profile.update'), $data);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        $student = User::factory()->student()->create();

        $payload = array_merge([
            'name' => '更新後の名前',
            'bio' => '更新後の自己紹介',
        ], $overrides);

        $response = $this->actingAs($student)->patchJson(route('settings.profile.update'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
    }

    public function test_coach_validation_passes_with_valid_meeting_url(): void
    {
        $coach = User::factory()->coach()->create();

        $data = [
            'name' => '更新後のコーチ名',
            'bio' => '更新後の自己紹介',
            'meeting_url' => 'https://meet.google.com/new-room',
        ];

        $response = $this->actingAs($coach)->patch(route('settings.profile.update'), $data);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);
    }

    #[DataProvider('invalidMeetingUrlPayloads')]
    public function test_coach_meeting_url_validation_fails(string $meetingUrl): void
    {
        $coach = User::factory()->coach()->create();

        $payload = [
            'name' => '更新後のコーチ名',
            'bio' => '更新後の自己紹介',
            'meeting_url' => $meetingUrl,
        ];

        $response = $this->actingAs($coach)->patchJson(route('settings.profile.update'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('meeting_url');
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'name 未指定で 422' => [['name' => ''], 'name'],
            'name 51 文字で 422' => [['name' => str_repeat('a', 51)], 'name'],
            'bio 1001 文字で 422' => [['bio' => str_repeat('a', 1001)], 'bio'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidMeetingUrlPayloads(): array
    {
        return [
            'meeting_url が URL 形式でない場合 422' => [
                'invalid-url',
            ],
            'meeting_url が 501 文字の場合 422' => [
                'https://example.com/'.str_repeat('a', 482),
            ],
        ];
    }
}
