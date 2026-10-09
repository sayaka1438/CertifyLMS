<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\SettingsProfile;

use App\Models\User;
use App\UseCases\SettingsProfile\DestroyAvatarAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DestroyAvatarActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_avatar(): void
    {
        Storage::fake('public');

        $path = 'avatars/avatar.jpg';
        Storage::disk('public')->put($path, 'avatar');

        $student = User::factory()->student()->create([
            'avatar_url' => $path,
        ]);

        $result = app(DestroyAvatarAction::class)($student);

        $this->assertNull($result->getRawOriginal('avatar_url'));

        Storage::disk('public')->assertMissing($path);
    }
}
