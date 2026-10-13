<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Certification;
use App\Models\User;
use App\Notifications\AdminAnnouncementNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()
            ->where('email', 'admin@certify-lms.test')
            ->first();

        $student = User::query()
            ->where('email', 'student@certify-lms.test')
            ->first();

        $certification = Certification::query()
            ->published()
            ->orderBy('created_at')
            ->first();

        if ($admin === null || $student === null || $certification === null) {
            return;
        }

        $allStudentsAnnouncement = Announcement::factory()->create([
            'title' => '全受講生向けのお知らせ',
            'body' => '全受講生向けのお知らせ本文です。',
            'dispatched_count' => 1,
            'dispatched_at' => now()->subDays(3),
            'created_by_user_id' => $admin->id,
        ]);

        $certificationAnnouncement = Announcement::factory()
            ->forCertification($certification)
            ->create([
                'title' => '資格指定のお知らせ',
                'body' => "「{$certification->name}」を受講中の方へのお知らせです。",
                'dispatched_count' => 1,
                'dispatched_at' => now()->subDays(2),
                'created_by_user_id' => $admin->id,
            ]);

        $userAnnouncement = Announcement::factory()
            ->forUser($student)
            ->create([
                'title' => 'ユーザー指定のお知らせ',
                'body' => "{$student->name}さんへのお知らせです。",
                'dispatched_count' => 1,
                'dispatched_at' => now()->subDay(),
                'created_by_user_id' => $admin->id,
            ]);

        $this->createNotification($student, $allStudentsAnnouncement);
        $this->createNotification($student, $certificationAnnouncement);
        $this->createNotification($student, $userAnnouncement);
    }

    private function createNotification(
        User $student,
        Announcement $announcement,
    ): void {
        $student->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => AdminAnnouncementNotification::class,
            'data' => [
                'title' => $announcement->title,
                'body' => $announcement->body,
                'message' => $announcement->body,
                'notification_type' => 'admin_announcement',
            ],
            'read_at' => null,
        ]);
    }
}
