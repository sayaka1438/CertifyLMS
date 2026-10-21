<?php

declare(strict_types=1);

namespace Tests\Feature\Http\AiChat;

use App\Enums\AiChatMessageStatus;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\Certification;
use App\Models\Chapter;
use App\Models\Enrollment;
use App\Models\Part;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiChatControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_in_progress_student_can_access_index(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $response = $this->actingAs($student)->get(route('ai-chat.index'));

        $response->assertOk();
        $response->assertViewIs('ai-chat.empty-state');
        $response->assertViewHas(
            'conversations',
            fn ($conversations) => $conversations->isEmpty(),
        );
    }

    public function test_student_with_conversations_is_redirected_to_latest_conversation(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'last_message_at' => now()->subDay(),
        ]);

        $latestConversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('ai-chat.index'));

        $response->assertRedirect(
            route('ai-chat.conversations.show', $latestConversation)
        );
    }

    public function test_graduated_student_cannot_access_index(): void
    {
        $student = User::factory()->student()->graduated()->create();

        $response = $this->actingAs($student)->get(route('ai-chat.index'));

        $response->assertForbidden();
    }

    public function test_coach_cannot_access_index(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $response = $this->actingAs($coach)->get(route('ai-chat.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('ai-chat.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_student_can_create_general_conversation(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $response = $this->actingAs($student)->post(route('ai-chat.conversations.store'), []);

        $conversation = AiChatConversation::query()
            ->where('user_id', $student->id)
            ->firstOrFail();

        $response->assertRedirect(route('ai-chat.conversations.show', $conversation));

        $this->assertDatabaseHas('ai_chat_conversations', [
            'id' => $conversation->id,
            'user_id' => $student->id,
            'section_id' => null,
            'title' => '新規相談',
        ]);
    }

    public function test_student_can_create_conversation_from_section(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->create();

        $enrollment = Enrollment::factory()
            ->for($student)
            ->for($certification)
            ->learning()
            ->create();

        $part = Part::factory()
            ->for($certification)
            ->create();
        $chapter = Chapter::factory()
            ->for($part)
            ->create();
        $section = Section::factory()
            ->for($chapter)
            ->create();

        $data = [
            'section_id' => $section->id,
        ];

        $response = $this->actingAs($student)->post(route('ai-chat.conversations.store'), $data);

        $conversation = AiChatConversation::query()
            ->where('user_id', $student->id)
            ->firstOrFail();

        $response->assertRedirect(route('ai-chat.conversations.show', $conversation));

        $this->assertDatabaseHas('ai_chat_conversations', [
            'id' => $conversation->id,
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'section_id' => $section->id,
            'title' => '新規相談',
        ]);
    }

    public function test_student_reuses_existing_conversation_for_same_section(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->create();

        $enrollment = Enrollment::factory()
            ->for($student)
            ->for($certification)
            ->learning()
            ->create();

        $part = Part::factory()
            ->for($certification)
            ->create();
        $chapter = Chapter::factory()
            ->for($part)
            ->create();
        $section = Section::factory()
            ->for($chapter)
            ->create();

        $existingConversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'section_id' => $section->id,
        ]);

        $data = [
            'section_id' => $section->id,
        ];

        $response = $this->actingAs($student)->post(route('ai-chat.conversations.store'), $data);

        $response->assertRedirect(route('ai-chat.conversations.show', $existingConversation));

        $this->assertDatabaseCount('ai_chat_conversations', 1);
    }

    public function test_student_can_create_conversation_as_json(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $response = $this->actingAs($student)->postJson(route('ai-chat.conversations.store'), []);

        $conversation = AiChatConversation::query()
            ->where('user_id', $student->id)
            ->firstOrFail();

        $response->assertStatus(201);
        $response->assertJsonPath('conversation.id', $conversation->id);
    }

    public function test_student_reuses_existing_conversation_as_json(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->create();

        $enrollment = Enrollment::factory()
            ->for($student)
            ->for($certification)
            ->learning()
            ->create();

        $part = Part::factory()
            ->for($certification)
            ->create();
        $chapter = Chapter::factory()
            ->for($part)
            ->create();
        $section = Section::factory()
            ->for($chapter)
            ->create();

        $existingConversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'section_id' => $section->id,
        ]);

        $data = [
            'section_id' => $section->id,
        ];

        $response = $this->actingAs($student)->postJson(route('ai-chat.conversations.store'), $data);

        $response->assertStatus(200);
        $response->assertJsonPath('conversation.id', $existingConversation->id);
    }

    public function test_student_can_view_own_conversation(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
        ]);

        $response = $this->actingAs($student)->get(route('ai-chat.conversations.show', $conversation));

        $response->assertOk();
        $response->assertViewIs('ai-chat.show');
        $response->assertViewHas('conversation', $conversation);
    }

    public function test_student_cannot_view_another_students_conversation(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $otherStudent->id,
        ]);

        $response = $this->actingAs($student)->get(route('ai-chat.conversations.show', $conversation));

        $response->assertForbidden();
    }

    public function test_student_can_view_own_conversation_as_json(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
        ]);

        $message = AiChatMessage::factory()->create([
            'ai_chat_conversation_id' => $conversation->id,
            'content' => 'PHPとはなんですか？',
        ]);

        $response = $this->actingAs($student)->getJson(route('ai-chat.conversations.show', $conversation));

        $response->assertOk();
        $response->assertJsonPath('conversation.id', $conversation->id);
        $response->assertJsonPath('messages.0.id', $message->id);
        $response->assertJsonPath('messages.0.content', 'PHPとはなんですか？');
    }

    public function test_student_can_update_own_conversation_title(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
        ]);

        $data = [
            'title' => 'PHPについての質問',
        ];

        $response = $this->actingAs($student)->patch(route('ai-chat.conversations.update', $conversation), $data);

        $response->assertRedirect(route('ai-chat.conversations.show', $conversation));
        $response->assertSessionHas('success', 'タイトルを更新しました。');

        $this->assertDatabaseHas('ai_chat_conversations', [
            'id' => $conversation->id,
            'title' => $data['title'],
        ]);
    }

    public function test_student_cannot_update_another_students_conversation(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $otherStudent->id,
        ]);

        $data = [
            'title' => 'PHPについての質問',
        ];

        $response = $this->actingAs($student)->patch(route('ai-chat.conversations.update', $conversation), $data);

        $response->assertForbidden();

        $this->assertDatabaseHas('ai_chat_conversations', [
            'id' => $conversation->id,
            'title' => $conversation->title,
        ]);
    }

    public function test_student_can_delete_own_conversation(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
        ]);

        $response = $this->actingAs($student)->delete(route('ai-chat.conversations.destroy', $conversation));

        $response->assertRedirect(route('ai-chat.index'));
        $response->assertSessionHas('success', '会話を削除しました。');

        $this->assertDatabaseMissing('ai_chat_conversations', [
            'id' => $conversation->id,
        ]);
    }

    public function test_student_cannot_delete_another_students_conversation(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $otherStudent->id,
        ]);

        $response = $this->actingAs($student)->delete(route('ai-chat.conversations.destroy', $conversation));

        $response->assertForbidden();

        $this->assertDatabaseHas('ai_chat_conversations', [
            'id' => $conversation->id,
        ]);
    }

    public function test_student_can_send_message_to_own_conversation(): void
    {
        config([
            'ai-chat.gemini.api_key' => 'test-api-key',
        ]);

        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'PHPはサーバーサイドで使われるプログラミング言語です。'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 10,
                    'candidatesTokenCount' => 20,
                ],
            ], 200),
        ]);

        $student = User::factory()->student()->inProgress()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
        ]);

        $data = [
            'content' => 'PHPとはなんですか？',
        ];

        $response = $this->actingAs($student)->postJson(route('ai-chat.conversations.messages.store', $conversation), $data);

        $response->assertStatus(200);

        $response->assertJsonPath(
            'user_message.content',
            $data['content']
        );

        $response->assertJsonPath(
            'assistant_message.content',
            'PHPはサーバーサイドで使われるプログラミング言語です。'
        );

        $response->assertJsonPath(
            'assistant_message.status',
            AiChatMessageStatus::Completed->value
        );
    }

    public function test_store_message_returns_bad_gateway_when_gemini_fails(): void
    {
        config([
            'ai-chat.gemini.api_key' => 'test-api-key',
        ]);

        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $student = User::factory()->student()->inProgress()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
        ]);

        $data = [
            'content' => 'PHPとはなんですか？',
        ];

        $response = $this->actingAs($student)->postJson(route('ai-chat.conversations.messages.store', $conversation), $data);

        $response->assertStatus(502);

        $response->assertJsonPath(
            'user_message.content',
            $data['content']
        );

        $response->assertJsonPath(
            'assistant_message.status',
            AiChatMessageStatus::Error->value
        );
    }

    public function test_student_cannot_send_message_to_another_students_conversation(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $otherStudent->id,
        ]);

        $response = $this->actingAs($student)->postJson(route('ai-chat.conversations.messages.store', $conversation), [
            'content' => 'PHPとはなんですか？',
        ]);

        $response->assertForbidden();
    }
}
