<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Announcement;

use App\Enums\AnnouncementTargetType;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_dispatch_announcement(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        User::factory()->student()->inProgress()->create();

        $data = [
            'title' => '全受講生向けのお知らせ',
            'body' => '全受講生向けのお知らせ本文です。',
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ];

        $response = $this->actingAs($admin)->post(route('admin.announcements.store'), $data);

        $announcement = Announcement::where('title', $data['title'])->firstOrFail();

        $response->assertRedirect(route('admin.announcements.show', $announcement));
        $response->assertSessionHas('success', 'お知らせを配信しました。（1件）');

        $this->assertDatabaseHas('announcements', [
            'title' => $data['title'],
            'body' => $data['body'],
            'target_type' => AnnouncementTargetType::AllStudents->value,
            'created_by_user_id' => $admin->id,
        ]);
    }

    public function test_student_cannot_dispatch_announcement(): void
    {
        $student = User::factory()->student()->create();

        $data = [
            'title' => '全受講生向けのお知らせ',
            'body' => '全受講生向けのお知らせ本文です。',
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ];

        $response = $this->actingAs($student)->post(route('admin.announcements.store'), $data);

        $response->assertForbidden();
    }

    public function test_coach_cannot_dispatch_announcement(): void
    {
        $coach = User::factory()->coach()->create();

        $data = [
            'title' => '全受講生向けのお知らせ',
            'body' => '全受講生向けのお知らせ本文です。',
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ];

        $response = $this->actingAs($coach)->post(route('admin.announcements.store'), $data);

        $response->assertForbidden();
    }
}
