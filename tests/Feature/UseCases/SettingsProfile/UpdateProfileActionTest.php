<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\SettingsProfile;

use App\Models\User;
use App\UseCases\SettingsProfile\UpdateProfileAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProfileActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_student_profile(): void
    {
        $student = User::factory()->student()->create();

        $validated = [
            'name' => '更新後の名前',
            'bio' => '更新後の自己紹介',
        ];

        $result = app(UpdateProfileAction::class)(
            user: $student,
            validated: $validated,
        );

        $this->assertSame($validated['name'], $result->name);
        $this->assertSame($validated['bio'], $result->bio);
    }

    public function test_updates_coach_profile_with_meeting_url(): void
    {
        $coach = User::factory()->coach()->create();

        $validated = [
            'name' => '更新後のコーチ名',
            'bio' => '更新後の自己紹介',
            'meeting_url' => 'https://meet.google.com/new-room',
        ];

        $result = app(UpdateProfileAction::class)(
            user: $coach,
            validated: $validated,
        );

        $this->assertSame($validated['meeting_url'], $result->meeting_url);
    }

    public function test_student_does_not_update_meeting_url(): void
    {
        $student = User::factory()->student()->create([
            'meeting_url' => 'https://meet.google.com/original-room',
        ]);

        $validated = [
            'name' => '更新後の名前',
            'bio' => '更新後の自己紹介',
            'meeting_url' => 'https://meet.google.com/changed-room',
        ];

        $result = app(UpdateProfileAction::class)(
            user: $student,
            validated: $validated,
        );

        $this->assertSame('https://meet.google.com/original-room', $result->meeting_url);
    }
}
