<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaReply;

use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $data = [
            'body' => '回答内容です。',
        ];

        $response = $this->actingAs($student)->post(route('qa-board.replies.store', $thread), $data);

        $response->assertRedirect(route('qa-board.show', $thread));
        $response->assertSessionHas('success', '回答を投稿しました。');

        $this->assertDatabaseHas('qa_replies', [
            'qa_thread_id' => $thread->id,
            'user_id' => $student->id,
            'body' => $data['body'],
        ]);
    }

    public function test_student_cannot_create_reply_for_draft_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->draft()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $data = [
            'body' => '回答内容です。',
        ];

        $response = $this->actingAs($student)->post(route('qa-board.replies.store', $thread), $data);

        $response->assertForbidden();
    }

    public function test_coach_can_create_reply(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $admin = User::factory()->admin()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $data = [
            'body' => '回答内容です。',
        ];

        $response = $this->actingAs($coach)->post(route('qa-board.replies.store', $thread), $data);

        $response->assertRedirect(route('qa-board.show', $thread));
        $response->assertSessionHas('success', '回答を投稿しました。');

        $this->assertDatabaseHas('qa_replies', [
            'qa_thread_id' => $thread->id,
            'user_id' => $coach->id,
            'body' => $data['body'],
        ]);
    }

    public function test_coach_cannot_create_reply_for_unassigned_certification(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $data = [
            'body' => '回答内容です。',
        ];

        $response = $this->actingAs($coach)->post(route('qa-board.replies.store', $thread), $data);

        $response->assertForbidden();
    }

    public function test_body_required_when_creating_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $data = [
            'body' => '',
        ];

        $response = $this->actingAs($student)->post(route('qa-board.replies.store', $thread), $data);

        $response->assertSessionHasErrors('body');
    }

    public function test_body_max_length_when_creating_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $data = [
            'body' => str_repeat('a', 5001),
        ];

        $response = $this->actingAs($student)->post(route('qa-board.replies.store', $thread), $data);

        $response->assertSessionHasErrors('body');
    }

    public function test_admin_cannot_create_reply(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $data = [
            'body' => '回答内容です。',
        ];

        $response = $this->actingAs($admin)->post(route('qa-board.replies.store', $thread), $data);

        $response->assertForbidden();
    }

    public function test_student_can_update_own_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $reply = QaReply::factory()
            ->for($thread, 'thread')
            ->for($student)
            ->create();

        $data = [
            'body' => '更新後の回答内容です。',
        ];

        $response = $this->actingAs($student)->patch(route('qa-board.replies.update', [$thread, $reply]), $data);

        $response->assertRedirect(route('qa-board.show', $thread));
        $response->assertSessionHas('success', '回答を更新しました。');

        $this->assertDatabaseHas('qa_replies', [
            'qa_thread_id' => $thread->id,
            'user_id' => $student->id,
            'body' => $data['body'],
        ]);
    }

    public function test_student_cannot_update_other_students_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $reply = QaReply::factory()
            ->for($thread, 'thread')
            ->for($otherStudent)
            ->create();

        $data = [
            'body' => '更新後の回答内容です。',
        ];

        $response = $this->actingAs($student)->patch(route('qa-board.replies.update', [$thread, $reply]), $data);

        $response->assertForbidden();

        $this->assertDatabaseHas('qa_replies', [
            'qa_thread_id' => $thread->id,
            'user_id' => $otherStudent->id,
            'body' => $reply->body,
        ]);
    }

    public function test_coach_can_update_own_reply(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $admin = User::factory()->admin()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $reply = QaReply::factory()
            ->for($thread, 'thread')
            ->for($coach)
            ->create();

        $data = [
            'body' => '更新後の回答内容です。',
        ];

        $response = $this->actingAs($coach)->patch(route('qa-board.replies.update', [$thread, $reply]), $data);

        $response->assertRedirect(route('qa-board.show', $thread));

        $this->assertDatabaseHas('qa_replies', [
            'qa_thread_id' => $thread->id,
            'user_id' => $coach->id,
            'body' => $data['body'],
        ]);
    }

    public function test_admin_cannot_update_reply(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $reply = QaReply::factory()
            ->for($thread, 'thread')
            ->for($student)
            ->create();

        $data = [
            'body' => '更新後の回答内容です。',
        ];

        $response = $this->actingAs($admin)->patch(route('qa-board.replies.update', [$thread, $reply]), $data);

        $response->assertForbidden();

        $this->assertDatabaseHas('qa_replies', [
            'id' => $reply->id,
            'body' => $reply->body,
        ]);
    }

    public function test_student_can_delete_own_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $reply = QaReply::factory()
            ->for($thread, 'thread')
            ->for($student)
            ->create();

        $response = $this->actingAs($student)->delete(route('qa-board.replies.destroy', [$thread, $reply]));

        $response->assertRedirect(route('qa-board.show', $thread));
        $response->assertSessionHas('success', '回答を削除しました。');

        $this->assertDatabaseMissing('qa_replies', [
            'id' => $reply->id,
        ]);
    }

    public function test_student_cannot_delete_other_students_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $reply = QaReply::factory()
            ->for($thread, 'thread')
            ->for($otherStudent)
            ->create();

        $response = $this->actingAs($student)->delete(route('qa-board.replies.destroy', [$thread, $reply]));

        $response->assertForbidden();

        $this->assertDatabaseHas('qa_replies', [
            'id' => $reply->id,
        ]);
    }

    public function test_coach_can_delete_own_reply(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $admin = User::factory()->admin()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $reply = QaReply::factory()
            ->for($thread, 'thread')
            ->for($coach)
            ->create();

        $response = $this->actingAs($coach)->delete(route('qa-board.replies.destroy', [$thread, $reply]));

        $response->assertRedirect(route('qa-board.show', $thread));

        $this->assertDatabaseMissing('qa_replies', [
            'id' => $reply->id,
        ]);
    }

    public function test_admin_can_delete_reply(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $reply = QaReply::factory()
            ->for($thread, 'thread')
            ->for($student)
            ->create();

        $response = $this->actingAs($admin)->delete(route('admin.qa-board.replies.destroy', [$thread, $reply]));

        $response->assertRedirect(route('admin.qa-board.show', $thread));

        $this->assertDatabaseMissing('qa_replies', [
            'id' => $reply->id,
        ]);
    }

    public function test_reply_from_another_thread_returns_not_found(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $otherThread = QaThread::factory()
            ->for($certification)
            ->create();

        $reply = QaReply::factory()
            ->for($otherThread, 'thread')
            ->for($student)
            ->create();

        $data = [
            'body' => '更新後の回答内容です。',
        ];

        $response = $this->actingAs($student)->patch(route('qa-board.replies.update', [$thread, $reply]), $data);

        $response->assertNotFound();
    }
}
