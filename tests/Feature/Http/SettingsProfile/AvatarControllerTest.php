<?php

declare(strict_types=1);

namespace Tests\Feature\Http\SettingsProfile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_upload_avatar(): void
    {
        Storage::fake('public');

        $student = User::factory()->student()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($student)->post(route('settings.avatar.store'), [
            'avatar' => $file,
        ]);

        $response->assertRedirect(route('settings.profile.edit'));
        $response->assertSessionHas('success', 'アバター画像を更新しました。');

        $student->refresh();

        $path = $student->getRawOriginal('avatar_url');

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_uploading_new_avatar_deletes_old_avatar(): void
    {
        Storage::fake('public');

        $oldPath = 'avatars/old-avatar.jpg';

        Storage::disk('public')->put($oldPath, 'old avatar');

        $student = User::factory()->student()->create([
            'avatar_url' => $oldPath,
        ]);

        $file = UploadedFile::fake()->image('new-avatar.jpg');

        $response = $this->actingAs($student)->post(route('settings.avatar.store'), [
            'avatar' => $file,
        ]);

        $response->assertRedirect(route('settings.profile.edit'));

        $student->refresh();

        $newPath = $student->getRawOriginal('avatar_url');

        $this->assertNotNull($newPath);
        $this->assertNotSame($oldPath, $newPath);

        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_student_can_delete_avatar(): void
    {
        Storage::fake('public');

        $path = 'avatars/avatar.jpg';

        Storage::disk('public')->put($path, 'avatar');

        $student = User::factory()->student()->create([
            'avatar_url' => $path,
        ]);

        $response = $this->actingAs($student)->delete(route('settings.avatar.destroy'));

        $response->assertRedirect(route('settings.profile.edit'));
        $response->assertSessionHas('success', 'アバター画像を削除しました。');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'avatar_url' => null,
        ]);

        Storage::disk('public')->assertMissing($path);
    }
}
