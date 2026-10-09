<?php

declare(strict_types=1);

namespace Tests\Feature\Http\SettingsProfile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_profile_settings(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('settings.profile.edit'));

        $response->assertOk();
        $response->assertViewIs('settings.profile');
    }

    public function test_graduated_student_can_view_profile_settings(): void
    {
        $student = User::factory()->student()->graduated()->create();

        $response = $this->actingAs($student)->get(route('settings.profile.edit'));

        $response->assertOk();
    }

    public function test_coach_can_view_profile_settings(): void
    {
        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)->get(route('settings.profile.edit'));

        $response->assertOk();
    }

    public function test_admin_can_view_profile_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('settings.profile.edit'));

        $response->assertOk();
    }

    public function test_student_can_update_own_profile(): void
    {
        $student = User::factory()->student()->create();

        $data = [
            'name' => '更新後の名前',
            'bio' => '更新後の自己紹介',
        ];

        $response = $this->actingAs($student)->patch(route('settings.profile.update'), $data);

        $response->assertRedirect(route('settings.profile.edit'));
        $response->assertSessionHas('success', 'プロフィールを更新しました。');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => $data['name'],
            'bio' => $data['bio'],
        ]);
    }

    public function test_coach_can_update_own_profile_with_meeting_url(): void
    {
        $coach = User::factory()->coach()->create();

        $data = [
            'name' => '更新後のコーチ名',
            'bio' => '更新後の自己紹介',
            'meeting_url' => 'https://meet.google.com/abc-defg-hij',
        ];

        $response = $this->actingAs($coach)->patch(route('settings.profile.update'), $data);

        $response->assertRedirect(route('settings.profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $coach->id,
            'name' => $data['name'],
            'bio' => $data['bio'],
            'meeting_url' => $data['meeting_url'],
        ]);
    }

    public function test_student_cannot_update_meeting_url(): void
    {
        $student = User::factory()->student()->create([
            'meeting_url' => 'https://meet.google.com/original-room',
        ]);

        $data = [
            'name' => '更新後の名前',
            'bio' => '更新後の自己紹介',
            'meeting_url' => 'https://meet.google.com/changed-room',
        ];

        $response = $this->actingAs($student)->patch(route('settings.profile.update'), $data);

        $response->assertRedirect(route('settings.profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => $data['name'],
            'bio' => $data['bio'],
            'meeting_url' => 'https://meet.google.com/original-room',
        ]);
    }

    public function test_admin_cannot_update_meeting_url(): void
    {
        $admin = User::factory()->admin()->create([
            'meeting_url' => 'https://meet.google.com/original-room',
        ]);

        $data = [
            'name' => '更新後の管理者名',
            'bio' => '更新後の自己紹介',
            'meeting_url' => 'https://meet.google.com/changed-room',
        ];

        $response = $this->actingAs($admin)->patch(route('settings.profile.update'), $data);

        $response->assertRedirect(route('settings.profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => $data['name'],
            'bio' => $data['bio'],
            'meeting_url' => 'https://meet.google.com/original-room',
        ]);
    }

    public function test_student_cannot_update_email(): void
    {
        $student = User::factory()->student()->create([
            'email' => 'student@example.com',
        ]);

        $data = [
            'name' => '更新後の名前',
            'bio' => '更新後の自己紹介',
            'email' => 'changed@example.com',
        ];

        $response = $this->actingAs($student)->patch(route('settings.profile.update'), $data);

        $response->assertRedirect(route('settings.profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => $data['name'],
            'bio' => $data['bio'],
            'email' => 'student@example.com',
        ]);
    }
}
