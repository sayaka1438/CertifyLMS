<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $data = [
            'certification_id' => $certification->id,
            'title' => 'Laravelの学習方法について',
            'body' => 'Laravelの効率的な学習方法を教えてください。',
        ];

        $response = $this->actingAs($student)->post(route('qa-board.store'), $data);

        $thread = QaThread::query()
            ->where('user_id', $student->id)
            ->where('title', $data['title'])
            ->firstOrFail();

        $response->assertRedirect(route('qa-board.show', $thread));
        $response->assertSessionHas('success', '質問を投稿しました。');

        $this->assertDatabaseHas('qa_threads', [
            'certification_id' => $certification->id,
            'user_id' => $student->id,
            'title' => $data['title'],
            'body' => $data['body'],
        ]);
    }

    public function test_student_cannot_create_thread_for_draft_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->draft()->create();

        $data = [
            'certification_id' => $certification->id,
            'title' => 'Laravelの学習方法について',
            'body' => 'Laravelの効率的な学習方法を教えてください。',
        ];

        $response = $this->actingAs($student)->post(route('qa-board.store'), $data);

        $response->assertSessionHasErrors('certification_id');

        $this->assertDatabaseMissing('qa_threads', [
            'user_id' => $student->id,
            'certification_id' => $certification->id,
            'title' => $data['title'],
        ]);
    }

    public function test_title_required_when_creating_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $data = [
            'certification_id' => $certification->id,
            'title' => '',
            'body' => 'Laravelの効率的な学習方法を教えてください。',
        ];

        $response = $this->actingAs($student)->post(route('qa-board.store'), $data);

        $response->assertSessionHasErrors('title');
    }

    public function test_title_max_length_when_creating_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $data = [
            'certification_id' => $certification->id,
            'title' => str_repeat('a', 201),
            'body' => 'Laravelの効率的な学習方法を教えてください。',
        ];

        $response = $this->actingAs($student)->post(route('qa-board.store'), $data);

        $response->assertSessionHasErrors('title');
    }

    public function test_body_required_when_creating_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $data = [
            'certification_id' => $certification->id,
            'title' => 'Laravelの学習方法について',
            'body' => '',
        ];

        $response = $this->actingAs($student)->post(route('qa-board.store'), $data);

        $response->assertSessionHasErrors('body');
    }

    public function test_body_max_length_when_creating_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $data = [
            'certification_id' => $certification->id,
            'title' => 'Laravelの学習方法について',
            'body' => str_repeat('a', 5001),
        ];

        $response = $this->actingAs($student)->post(route('qa-board.store'), $data);

        $response->assertSessionHasErrors('body');
    }

    public function test_coach_cannot_create_thread(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $data = [
            'certification_id' => $certification->id,
            'title' => 'Laravelの学習方法について',
            'body' => 'Laravelの効率的な学習方法を教えてください。',
        ];

        $response = $this->actingAs($coach)
            ->post(route('qa-board.store'), $data);

        $response->assertForbidden();
    }

    public function test_admin_cannot_create_thread(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $data = [
            'certification_id' => $certification->id,
            'title' => 'Laravelの学習方法について',
            'body' => 'Laravelの効率的な学習方法を教えてください。',
        ];

        $response = $this->actingAs($admin)
            ->post(route('qa-board.store'), $data);

        $response->assertForbidden();
    }

    public function test_student_can_update_own_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->for($certification)
            ->create();

        $data = [
            'title' => '更新後のタイトル',
            'body' => '更新後の本文です。',
        ];

        $response = $this->actingAs($student)
            ->patch(route('qa-board.update', $thread), $data);

        $response->assertRedirect(route('qa-board.show', $thread));
        $response->assertSessionHas('success', '質問を更新しました。');

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'user_id' => $student->id,
            'certification_id' => $certification->id,
            'title' => $data['title'],
            'body' => $data['body'],
        ]);
    }

    public function test_student_cannot_update_other_students_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($otherStudent, 'user')
            ->for($certification)
            ->create();

        $data = [
            'title' => '更新後のタイトル',
            'body' => '更新後の本文です。',
        ];

        $response = $this->actingAs($student)->patch(route('qa-board.update', $thread), $data);

        $response->assertForbidden();

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'user_id' => $otherStudent->id,
            'title' => $thread->title,
            'body' => $thread->body,
        ]);
    }

    public function test_student_cannot_change_certification_when_updating_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();
        $otherCertification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->for($certification)
            ->create();

        $data = [
            'certification_id' => $otherCertification->id,
            'title' => '更新後のタイトル',
            'body' => '更新後の本文です。',
        ];

        $response = $this->actingAs($student)->patch(route('qa-board.update', $thread), $data);

        $response->assertRedirect(route('qa-board.show', $thread));

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'certification_id' => $certification->id,
            'title' => $data['title'],
            'body' => $data['body'],
        ]);
    }

    public function test_student_can_delete_own_thread_without_replies(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->for($certification)
            ->create();

        $response = $this->actingAs($student)->delete(route('qa-board.destroy', $thread));

        $response->assertRedirect(route('qa-board.index'));
        $response->assertSessionHas('success', '質問を削除しました。');

        $this->assertDatabaseMissing('qa_threads', [
            'id' => $thread->id,
        ]);
    }

    public function test_student_cannot_delete_own_thread_with_replies(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->for($certification)
            ->create();

        QaReply::factory()
            ->for($thread, 'thread')
            ->create();

        $response = $this->actingAs($student)->delete(route('qa-board.destroy', $thread));

        $response->assertForbidden();

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
        ]);
    }

    public function test_student_cannot_delete_other_students_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($otherStudent, 'user')
            ->for($certification)
            ->create();

        $response = $this->actingAs($student)->delete(route('qa-board.destroy', $thread));

        $response->assertForbidden();

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
        ]);
    }

    public function test_coach_cannot_delete_thread(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->for($certification)
            ->create();

        $response = $this->actingAs($coach)->delete(route('qa-board.destroy', $thread));

        $response->assertForbidden();

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
        ]);
    }

    public function test_admin_can_delete_thread(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->for($certification)
            ->create();

        QaReply::factory()
            ->for($thread, 'thread')
            ->create();

        $response = $this->actingAs($admin)
            ->delete(route('admin.qa-board.destroy', $thread));

        $response->assertRedirect(route('admin.qa-board.index'));

        $this->assertDatabaseMissing('qa_threads', [
            'id' => $thread->id,
        ]);
    }

    public function test_student_can_resolve_own_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->for($certification)
            ->create();

        $response = $this->actingAs($student)->post(route('qa-board.resolve', $thread));

        $response->assertRedirect(route('qa-board.show', $thread));
        $response->assertSessionHas('success', '質問を解決済みにマークしました。');

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Resolved->value,
        ]);

        $thread->refresh();

        $this->assertNotNull($thread->resolved_at);
    }

    public function test_student_cannot_resolve_already_resolved_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->for($certification)
            ->resolved()
            ->create();

        $response = $this->actingAs($student)->post(route('qa-board.resolve', $thread));

        $response->assertForbidden();

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Resolved->value,
        ]);
    }

    public function test_student_can_unresolve_own_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->for($certification)
            ->resolved()
            ->create();

        $response = $this->actingAs($student)->post(route('qa-board.unresolve', $thread));

        $response->assertRedirect(route('qa-board.show', $thread));
        $response->assertSessionHas('success', '質問を未解決に戻しました。');

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Open->value,
        ]);

        $thread->refresh();

        $this->assertNull($thread->resolved_at);
    }

    public function test_student_cannot_unresolve_already_open_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->for($certification)
            ->create();

        $response = $this->actingAs($student)->post(route('qa-board.unresolve', $thread));

        $response->assertForbidden();

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Open->value,
        ]);
    }

    public function test_student_cannot_resolve_other_students_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($otherStudent, 'user')
            ->for($certification)
            ->create();

        $response = $this->actingAs($student)->post(route('qa-board.resolve', $thread));

        $response->assertForbidden();

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Open->value,
        ]);

        $thread->refresh();

        $this->assertNull($thread->resolved_at);
    }

    public function test_coach_cannot_resolve_thread(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->for($certification)
            ->create();

        $response = $this->actingAs($coach)->post(route('qa-board.resolve', $thread));

        $response->assertForbidden();

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Open->value,
        ]);

        $thread->refresh();

        $this->assertNull($thread->resolved_at);
    }

    public function test_admin_cannot_resolve_thread(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->for($certification)
            ->create();

        $response = $this->actingAs($admin)->post(route('qa-board.resolve', $thread));

        $response->assertForbidden();

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Open->value,
        ]);

        $thread->refresh();

        $this->assertNull($thread->resolved_at);
    }
}
