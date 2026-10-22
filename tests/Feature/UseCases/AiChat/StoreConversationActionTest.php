<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\AiChat;

use App\Models\AiChatConversation;
use App\Models\Certification;
use App\Models\Chapter;
use App\Models\Enrollment;
use App\Models\Part;
use App\Models\Section;
use App\Models\User;
use App\UseCases\AiChat\StoreConversationAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreConversationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_conversation_uses_default_enrollment(): void
    {
        $student = User::factory()->student()->create();

        Enrollment::factory()
            ->for($student)
            ->learning()
            ->create();

        $defaultEnrollment = Enrollment::factory()
            ->for($student)
            ->learning()
            ->create();

        $student->update([
            'default_enrollment_id' => $defaultEnrollment->id,
        ]);

        $conversation = app(StoreConversationAction::class)(
            user: $student,
            validated: [],
        );

        $this->assertSame($student->id, $conversation->user_id);
        $this->assertSame($defaultEnrollment->id, $conversation->enrollment_id);
        $this->assertNull($conversation->section_id);
        $this->assertSame('新規相談', $conversation->title);
        $this->assertTrue($conversation->auto_title_enabled);

        $this->assertDatabaseHas('ai_chat_conversations', [
            'id' => $conversation->id,
            'user_id' => $student->id,
            'enrollment_id' => $defaultEnrollment->id,
            'section_id' => null,
            'title' => '新規相談',
        ]);
    }

    public function test_general_conversation_has_no_enrollment_when_default_enrollment_is_null(): void
    {
        $student = User::factory()->student()->create();

        $conversation = app(StoreConversationAction::class)(
            user: $student,
            validated: [],
        );

        $this->assertNull($conversation->enrollment_id);
    }

    public function test_section_conversation_uses_matching_enrollment(): void
    {
        $student = User::factory()->student()->create();
        $certification = Certification::factory()->published()->create();

        $part = Part::factory()
            ->for($certification)
            ->create();

        $chapter = Chapter::factory()
            ->for($part)
            ->create();

        $section = Section::factory()
            ->for($chapter)
            ->create();

        $enrollment = Enrollment::factory()
            ->for($student)
            ->for($certification)
            ->learning()
            ->create();

        $conversation = app(StoreConversationAction::class)(
            user: $student,
            validated: [
                'section_id' => $section->id,
            ],
        );

        $this->assertSame($student->id, $conversation->user_id);
        $this->assertSame($enrollment->id, $conversation->enrollment_id);
        $this->assertSame($section->id, $conversation->section_id);
        $this->assertSame($section->title, $conversation->title);
        $this->assertTrue($conversation->auto_title_enabled);

        $this->assertDatabaseHas('ai_chat_conversations', [
            'id' => $conversation->id,
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'section_id' => $section->id,
            'title' => $section->title,
        ]);
    }

    public function test_reuses_existing_conversation_for_same_section(): void
    {
        $student = User::factory()->student()->create();
        $certification = Certification::factory()->published()->create();

        $part = Part::factory()
            ->for($certification)
            ->create();

        $chapter = Chapter::factory()
            ->for($part)
            ->create();

        $section = Section::factory()
            ->for($chapter)
            ->create();

        $enrollment = Enrollment::factory()
            ->for($student)
            ->for($certification)
            ->learning()
            ->create();

        $existingConversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'section_id' => $section->id,
        ]);

        $conversation = app(StoreConversationAction::class)(
            user: $student,
            validated: [
                'section_id' => $section->id,
            ],
        );

        $this->assertTrue($conversation->is($existingConversation));
        $this->assertDatabaseCount('ai_chat_conversations', 1);
    }
}
