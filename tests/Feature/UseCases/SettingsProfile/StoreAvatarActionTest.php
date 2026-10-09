<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\SettingsProfile;

use App\Models\User;
use App\UseCases\SettingsProfile\StoreAvatarAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreAvatarActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_avatar(): void
    {
        Storage::fake('public');

        $student = User::factory()->student()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $result = app(StoreAvatarAction::class)(
            user: $student,
            file: $file,
        );

        $path = $result->getRawOriginal('avatar_url');

        $this->assertNotNull($path);

        Storage::disk('public')->assertExists($path);
    }

    public function test_deletes_old_avatar_when_storing_new_avatar(): void
    {
        Storage::fake('public');

        $oldPath = 'avatars/old-avatar.jpg';
        Storage::disk('public')->put($oldPath, 'old avatar');

        $student = User::factory()->student()->create([
            'avatar_url' => $oldPath,
        ]);

        $file = UploadedFile::fake()->image('new-avatar.jpg');

        $result = app(StoreAvatarAction::class)(
            user: $student,
            file: $file,
        );

        $newPath = $result->getRawOriginal('avatar_url');

        $this->assertNotNull($newPath);
        $this->assertNotSame($oldPath, $newPath);

        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($oldPath);
    }
}
