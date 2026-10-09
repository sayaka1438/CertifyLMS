<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\SettingsProfile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreAvatarRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_avatar(): void
    {
        Storage::fake('public');

        $student = User::factory()->student()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $data = [
            'avatar' => $file,
        ];

        $response = $this->actingAs($student)->post(route('settings.avatar.store'), $data);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);
    }

    public function test_validation_fails_when_avatar_is_missing(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->postJson(route('settings.avatar.store'));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('avatar');
    }

    public function test_validation_fails_when_avatar_is_not_image(): void
    {
        Storage::fake('public');

        $student = User::factory()->student()->create();
        $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $data = [
            'avatar' => $file,
        ];

        $response = $this->actingAs($student)->postJson(route('settings.avatar.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('avatar');
    }

    public function test_validation_fails_when_avatar_exceeds_max_size(): void
    {
        Storage::fake('public');

        $student = User::factory()->student()->create();
        $file = UploadedFile::fake()->image('avatar.jpg')->size(2049);

        $data = [
            'avatar' => $file,
        ];

        $response = $this->actingAs($student)->postJson(route('settings.avatar.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('avatar');
    }
}
