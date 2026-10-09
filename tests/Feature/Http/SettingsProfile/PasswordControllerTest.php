<?php

declare(strict_types=1);

namespace Tests\Feature\Http\SettingsProfile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_update_password(): void
    {
        $student = User::factory()->student()->create([
            'password' => 'old-password',
        ]);

        $data = [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $response = $this->actingAs($student)->put(route('settings.password.update'), $data);

        $response->assertRedirect(route('settings.profile.edit', ['tab' => 'password']));
        $response->assertSessionHas('success', 'パスワードを更新しました。');

        $student->refresh();

        $this->assertTrue(
            Hash::check($data['password'], $student->password)
        );
    }

    public function test_password_update_fails_when_current_password_is_incorrect(): void
    {
        $student = User::factory()->student()->create([
            'password' => 'old-password',
        ]);

        $data = [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $response = $this->actingAs($student)->put(route('settings.password.update'), $data);

        $response->assertSessionHasErrors(
            ['current_password'],
            null,
            'updatePassword'
        );

        $student->refresh();

        $this->assertTrue(
            Hash::check('old-password', $student->password)
        );
    }

    public function test_password_update_fails_when_password_confirmation_does_not_match(): void
    {
        $student = User::factory()->student()->create([
            'password' => 'old-password',
        ]);

        $data = [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ];

        $response = $this->actingAs($student)->put(route('settings.password.update'), $data);

        $response->assertSessionHasErrors(
            ['password'],
            null,
            'updatePassword'
        );

        $student->refresh();

        $this->assertTrue(
            Hash::check('old-password', $student->password)
        );
    }
}
