<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AiChatConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_relation_returns_owner_user(): void
    {
        $student = User::factory()->student()->create();
        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
        ]);

        $owner = $conversation->user;

        $this->assertTrue($owner->is($student));
    }

    public function test_enrollment_relation_returns_related_enrollment(): void
    {
        $enrollment = Enrollment::factory()->create();
        $conversation = AiChatConversation::factory()->create([
            'enrollment_id' => $enrollment->id,
        ]);

        $relatedEnrollment = $conversation->enrollment;

        $this->assertTrue($relatedEnrollment->is($enrollment));
    }

    public function test_section_relation_returns_related_section(): void
    {
        $section = Section::factory()->create();
        $conversation = AiChatConversation::factory()->create([
            'section_id' => $section->id,
        ]);

        $relatedSection = $conversation->section;

        $this->assertTrue($relatedSection->is($section));
    }

    public function test_messages_relation_returns_related_messages(): void
    {
        $conversation = AiChatConversation::factory()->create();
        $message = AiChatMessage::factory()->create([
            'ai_chat_conversation_id' => $conversation->id,
        ]);

        $relatedMessages = $conversation->messages;

        $this->assertCount(1, $relatedMessages);
        $this->assertTrue($relatedMessages->contains($message));
    }

    public function test_auto_title_enabled_is_cast_to_boolean(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'auto_title_enabled' => 1,
        ]);

        $this->assertIsBool($conversation->auto_title_enabled);
        $this->assertTrue($conversation->auto_title_enabled);
    }

    public function test_last_message_at_is_cast_to_datetime(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'last_message_at' => '2026-10-20 12:00:00',
        ]);

        $this->assertInstanceOf(
            Carbon::class,
            $conversation->last_message_at,
        );
    }
}
