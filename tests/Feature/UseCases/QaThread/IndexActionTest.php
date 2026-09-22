<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use App\UseCases\QaThread\IndexAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IndexActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_access_only_published_certifications(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $publishedCertification = Certification::factory()->published()->create();
        $draftCertification = Certification::factory()->draft()->create();

        $publishedThread = QaThread::factory()
            ->for($publishedCertification)
            ->create();
        $draftThread = QaThread::factory()
            ->for($draftCertification)
            ->create();

        $result = app(IndexAction::class)($student, []);

        $threadIds = $result['threads']->pluck('id');
        $certificationIds = $result['certifications']->pluck('id');

        $this->assertTrue($threadIds->contains($publishedThread->id));
        $this->assertFalse($threadIds->contains($draftThread->id));

        $this->assertTrue($certificationIds->contains($publishedCertification->id));
        $this->assertFalse($certificationIds->contains($draftCertification->id));
    }

    public function test_coach_can_access_only_assigned_published_certifications(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $admin = User::factory()->admin()->inProgress()->create();

        $assignedPublishedCertification = Certification::factory()->published()->create();
        $unassignedPublishedCertification = Certification::factory()->published()->create();
        $assignedDraftCertification = Certification::factory()->draft()->create();

        $assignedPublishedCertification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $assignedDraftCertification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignedPublishedThread = QaThread::factory()
            ->for($assignedPublishedCertification)
            ->create();
        $unassignedPublishedThread = QaThread::factory()
            ->for($unassignedPublishedCertification)
            ->create();
        $assignedDraftThread = QaThread::factory()
            ->for($assignedDraftCertification)
            ->create();

        $result = app(IndexAction::class)($coach, []);

        $threadIds = $result['threads']->pluck('id');
        $certificationIds = $result['certifications']->pluck('id');

        $this->assertTrue($threadIds->contains($assignedPublishedThread->id));
        $this->assertFalse($threadIds->contains($unassignedPublishedThread->id));
        $this->assertFalse($threadIds->contains($assignedDraftThread->id));

        $this->assertTrue($certificationIds->contains($assignedPublishedCertification->id));
        $this->assertFalse($certificationIds->contains($unassignedPublishedCertification->id));
        $this->assertFalse($certificationIds->contains($assignedDraftCertification->id));
    }

    public function test_admin_can_access_all_certifications(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();

        $publishedCertification = Certification::factory()->published()->create();
        $draftCertification = Certification::factory()->draft()->create();

        $publishedThread = QaThread::factory()
            ->for($publishedCertification)
            ->create();
        $draftThread = QaThread::factory()
            ->for($draftCertification)
            ->create();

        $result = app(IndexAction::class)($admin, []);

        $threadIds = $result['threads']->pluck('id');
        $certificationIds = $result['certifications']->pluck('id');

        $this->assertTrue($threadIds->contains($publishedThread->id));
        $this->assertTrue($threadIds->contains($draftThread->id));

        $this->assertTrue($certificationIds->contains($publishedCertification->id));
        $this->assertTrue($certificationIds->contains($draftCertification->id));
    }

    public function test_filters_threads_by_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $targetCertification = Certification::factory()->published()->create();
        $otherCertification = Certification::factory()->published()->create();

        $targetThread = QaThread::factory()
            ->for($targetCertification)
            ->create();
        $otherThread = QaThread::factory()
            ->for($otherCertification)
            ->create();

        $result = app(IndexAction::class)($student, [
            'certification_id' => $targetCertification->id,
        ]);

        $threadIds = $result['threads']->pluck('id');

        $this->assertTrue($threadIds->contains($targetThread->id));
        $this->assertFalse($threadIds->contains($otherThread->id));
    }

    public function test_filters_unresolved_threads_by_status(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $openThread = QaThread::factory()
            ->for($certification)
            ->create();
        $resolvedThread = QaThread::factory()
            ->for($certification)
            ->resolved()
            ->create();

        $result = app(IndexAction::class)($student, [
            'status' => 'unresolved',
        ]);

        $threadIds = $result['threads']->pluck('id');

        $this->assertTrue($threadIds->contains($openThread->id));
        $this->assertFalse($threadIds->contains($resolvedThread->id));
    }

    public function test_filters_resolved_threads_by_status(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $openThread = QaThread::factory()
            ->for($certification)
            ->create();
        $resolvedThread = QaThread::factory()
            ->for($certification)
            ->resolved()
            ->create();

        $result = app(IndexAction::class)($student, [
            'status' => 'resolved',
        ]);

        $threadIds = $result['threads']->pluck('id');

        $this->assertTrue($threadIds->contains($resolvedThread->id));
        $this->assertFalse($threadIds->contains($openThread->id));
    }

    public function test_filters_threads_by_title_keyword(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $matchingThread = QaThread::factory()
            ->for($certification)
            ->create([
                'title' => 'Laravelについて',
            ]);

        $nonMatchingThread = QaThread::factory()
            ->for($certification)
            ->create([
                'title' => 'PHPについて',
            ]);

        $result = app(IndexAction::class)($student, [
            'keyword' => 'Laravel',
        ]);

        $threadIds = $result['threads']->pluck('id');

        $this->assertTrue($threadIds->contains($matchingThread->id));
        $this->assertFalse($threadIds->contains($nonMatchingThread->id));
    }

    public function test_filters_threads_by_body_keyword(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $matchingThread = QaThread::factory()
            ->for($certification)
            ->create([
                'body' => 'Laravelの認証について質問があります。',
            ]);
        $nonMatchingThread = QaThread::factory()
            ->for($certification)
            ->create([
                'body' => 'PHPの配列について質問があります。',
            ]);

        $result = app(IndexAction::class)($student, [
            'keyword' => 'Laravel',
        ]);

        $threadIds = $result['threads']->pluck('id');

        $this->assertTrue($threadIds->contains($matchingThread->id));
        $this->assertFalse($threadIds->contains($nonMatchingThread->id));
    }

    public function test_filters_threads_by_reply_body_keyword(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $matchingThread = QaThread::factory()
            ->for($certification)
            ->create();
        $nonMatchingThread = QaThread::factory()
            ->for($certification)
            ->create();

        QaReply::factory()
            ->for($matchingThread, 'thread')
            ->create([
                'body' => 'Laravelの認証について回答します。',
            ]);

        QaReply::factory()
            ->for($nonMatchingThread, 'thread')
            ->create([
                'body' => 'PHPの配列について回答します。',
            ]);

        $result = app(IndexAction::class)($student, [
            'keyword' => 'Laravel',
        ]);

        $threadIds = $result['threads']->pluck('id');

        $this->assertTrue($threadIds->contains($matchingThread->id));
        $this->assertFalse($threadIds->contains($nonMatchingThread->id));
    }

    public function test_threads_are_ordered_by_latest(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $olderThread = QaThread::factory()
            ->for($certification)
            ->create([
                'created_at' => now()->subDays(2),
            ]);
        $newerThread = QaThread::factory()
            ->for($certification)
            ->create([
                'created_at' => now()->subDay(),
            ]);

        $result = app(IndexAction::class)($student, []);

        $threadIds = $result['threads']->pluck('id')->all();

        $this->assertSame([
            $newerThread->id,
            $olderThread->id,
        ], $threadIds);
    }

    public function test_threads_are_paginated(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        QaThread::factory()
            ->count(3)
            ->for($certification)
            ->create();

        $result = app(IndexAction::class)($student, [], 2);

        $this->assertSame(2, $result['threads']->count());
        $this->assertSame(3, $result['threads']->total());
    }

    public function test_threads_include_replies_count(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        QaReply::factory()
            ->count(2)
            ->for($thread, 'thread')
            ->create();

        $result = app(IndexAction::class)($student, []);

        $resultThread = $result['threads']->firstWhere('id', $thread->id);

        $this->assertSame(2, $resultThread->replies_count);
    }

    public function test_threads_eager_load_required_relations(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $result = app(IndexAction::class)($student, []);

        $resultThread = $result['threads']->firstWhere('id', $thread->id);

        $this->assertTrue($resultThread->relationLoaded('user'));
        $this->assertTrue($resultThread->relationLoaded('certification'));
    }
}
