<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Announcement;

use App\Enums\AnnouncementTargetType;
use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\AdminAnnouncementNotification;
use App\UseCases\Announcement\StoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StoreActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_announcement_to_all_students(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->inProgress()->create();

        $data = [
            'title' => '全受講生向けのお知らせ',
            'body' => '全受講生向けのお知らせ本文です。',
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ];

        $announcement = app(StoreAction::class)(
            admin: $admin,
            validated: $data,
        );

        $this->assertSame($data['title'], $announcement->title);
        $this->assertSame($data['body'], $announcement->body);
        $this->assertSame(AnnouncementTargetType::AllStudents, $announcement->target_type);
        $this->assertSame($admin->id, $announcement->created_by_user_id);
        $this->assertSame(1, $announcement->dispatched_count);

        Notification::assertSentTo(
            $student,
            AdminAnnouncementNotification::class,
        );
    }

    public function test_dispatches_announcement_to_students_learning_target_certification(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();

        $student = User::factory()->student()->inProgress()->create();
        $passedStudent = User::factory()->student()->inProgress()->create();
        $failedStudent = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->create();

        Enrollment::factory()
            ->for($student)
            ->for($certification)
            ->learning()
            ->create();

        Enrollment::factory()
            ->for($passedStudent)
            ->for($certification)
            ->passed()
            ->create();

        Enrollment::factory()
            ->for($failedStudent)
            ->for($certification)
            ->failed()
            ->create();

        $data = [
            'title' => '資格指定のお知らせ',
            'body' => '資格指定のお知らせ本文です。',
            'target_type' => AnnouncementTargetType::Certification->value,
            'target_certification_id' => $certification->id,
        ];

        $announcement = app(StoreAction::class)(
            admin: $admin,
            validated: $data,
        );

        $this->assertSame($data['title'], $announcement->title);
        $this->assertSame(AnnouncementTargetType::Certification, $announcement->target_type);
        $this->assertSame($certification->id, $announcement->target_certification_id);
        $this->assertSame(1, $announcement->dispatched_count);

        Notification::assertSentTo(
            $student,
            AdminAnnouncementNotification::class,
        );

        Notification::assertNotSentTo(
            $passedStudent,
            AdminAnnouncementNotification::class,
        );

        Notification::assertNotSentTo(
            $failedStudent,
            AdminAnnouncementNotification::class,
        );
    }

    public function test_dispatches_announcement_to_target_user(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->inProgress()->create();

        $data = [
            'title' => 'ユーザー指定のお知らせ',
            'body' => 'ユーザー指定のお知らせ本文です。',
            'target_type' => AnnouncementTargetType::User->value,
            'target_user_id' => $student->id,
        ];

        $announcement = app(StoreAction::class)(
            admin: $admin,
            validated: $data,
        );

        $this->assertSame($data['title'], $announcement->title);
        $this->assertSame(AnnouncementTargetType::User, $announcement->target_type);
        $this->assertSame($student->id, $announcement->target_user_id);
        $this->assertSame(1, $announcement->dispatched_count);

        Notification::assertSentTo(
            $student,
            AdminAnnouncementNotification::class,
        );
    }

    public function test_does_not_dispatch_announcement_to_ineligible_users(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->inProgress()->create();
        $graduatedStudent = User::factory()->student()->graduated()->create();
        $coach = User::factory()->coach()->inProgress()->create();

        $data = [
            'title' => '全受講生向けのお知らせ',
            'body' => '全受講生向けのお知らせ本文です。',
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ];

        $announcement = app(StoreAction::class)(
            admin: $admin,
            validated: $data,
        );

        $this->assertSame(1, $announcement->dispatched_count);

        Notification::assertSentTo(
            $student,
            AdminAnnouncementNotification::class,
        );
        Notification::assertNotSentTo(
            $graduatedStudent,
            AdminAnnouncementNotification::class,
        );
        Notification::assertNotSentTo(
            $coach,
            AdminAnnouncementNotification::class,
        );
        Notification::assertNotSentTo(
            $admin,
            AdminAnnouncementNotification::class,
        );
    }

    public function test_stores_only_target_ids_matching_target_type(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->create();

        $data = [
            'title' => '全受講生向けのお知らせ',
            'body' => '全受講生向けのお知らせ本文です。',
            'target_type' => AnnouncementTargetType::AllStudents->value,
            'target_certification_id' => $certification->id,
            'target_user_id' => $student->id,
        ];

        $announcement = app(StoreAction::class)(
            admin: $admin,
            validated: $data,
        );

        $this->assertNull($announcement->target_certification_id);
        $this->assertNull($announcement->target_user_id);
    }

    public function test_stores_announcement_when_no_recipients_exist(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $certification = Certification::factory()->create();

        $data = [
            'title' => '配信対象0件のお知らせ',
            'body' => '配信対象0件のお知らせ本文です。',
            'target_type' => AnnouncementTargetType::Certification->value,
            'target_certification_id' => $certification->id,
        ];

        $announcement = app(StoreAction::class)(
            admin: $admin,
            validated: $data,
        );

        $this->assertSame(0, $announcement->dispatched_count);

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'target_type' => AnnouncementTargetType::Certification->value,
            'target_certification_id' => $certification->id,
            'dispatched_count' => 0,
        ]);

        Notification::assertNothingSent();
    }
}
