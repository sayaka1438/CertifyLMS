<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Announcement;

use App\Enums\AnnouncementTargetType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_data(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();

        $data = [
            'title' => '全受講生向けのお知らせ',
            'body' => '全受講生向けのお知らせ本文です。',
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ];

        $response = $this->actingAs($admin)->post(route('admin.announcements.store'), $data);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);
        $this->assertDatabaseHas('announcements', [
            'title' => $data['title'],
        ]);
    }

    public function test_target_certification_id_is_required_for_certification_target(): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'title' => '資格指定のお知らせ',
            'body' => '資格指定のお知らせ本文です。',
            'target_type' => AnnouncementTargetType::Certification->value,
        ];

        $response = $this->actingAs($admin)
            ->postJson(route('admin.announcements.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('target_certification_id');
    }

    public function test_target_user_id_is_required_for_user_target(): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'title' => 'ユーザー指定のお知らせ',
            'body' => 'ユーザー指定のお知らせ本文です。',
            'target_type' => AnnouncementTargetType::User->value,
        ];

        $response = $this->actingAs($admin)
            ->postJson(route('admin.announcements.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('target_user_id');
    }

    public function test_target_certification_id_must_exist(): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'title' => '資格指定のお知らせ',
            'body' => '資格指定のお知らせ本文です。',
            'target_type' => AnnouncementTargetType::Certification->value,
            'target_certification_id' => (string) Str::ulid(),
        ];

        $response = $this->actingAs($admin)
            ->postJson(route('admin.announcements.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('target_certification_id');
    }

    public function test_target_user_id_must_exist(): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'title' => 'ユーザー指定のお知らせ',
            'body' => 'ユーザー指定のお知らせ本文です。',
            'target_type' => AnnouncementTargetType::User->value,
            'target_user_id' => (string) Str::ulid(),
        ];

        $response = $this->actingAs($admin)
            ->postJson(route('admin.announcements.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('target_user_id');
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        $admin = User::factory()->admin()->create();

        $payload = array_merge([
            'title' => '全受講生向けのお知らせ',
            'body' => '全受講生向けのお知らせ本文です。',
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ], $overrides);

        $response = $this->actingAs($admin)->postJson(route('admin.announcements.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'title 未指定で 422' => [['title' => ''], 'title'],
            'title 201 文字で 422' => [['title' => str_repeat('a', 201)], 'title'],

            'body 未指定で 422' => [['body' => ''], 'body'],
            'body 5001 文字で 422' => [['body' => str_repeat('a', 5001)], 'body'],

            'target_type 未指定で 422' => [['target_type' => ''], 'target_type'],
            'target_type 不正値で 422' => [['target_type' => 'invalid'], 'target_type'],
        ];
    }
}
