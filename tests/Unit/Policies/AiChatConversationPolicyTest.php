<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\AiChatConversation;
use App\Models\User;
use App\Policies\AiChatConversationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiChatConversationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_any_conversations(): void
    {
        $student = User::factory()->student()->create();
        $policy = new AiChatConversationPolicy;

        $this->assertTrue($policy->viewAny($student));
    }

    public function test_student_can_manage_own_conversation(): void
    {
        $student = User::factory()->student()->create();
        $conversation = AiChatConversation::factory()->for($student)->create();
        $policy = new AiChatConversationPolicy;

        $this->assertTrue($policy->view($student, $conversation));
        $this->assertTrue($policy->update($student, $conversation));
        $this->assertTrue($policy->delete($student, $conversation));
    }

    public function test_student_cannot_manage_other_students_conversation(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $conversation = AiChatConversation::factory()->for($otherStudent)->create();
        $policy = new AiChatConversationPolicy;

        $this->assertFalse($policy->view($student, $conversation));
        $this->assertFalse($policy->update($student, $conversation));
        $this->assertFalse($policy->delete($student, $conversation));
    }

    public function test_coach_and_admin_cannot_access_ai_chat_conversations(): void
    {
        $coach = User::factory()->coach()->create();
        $admin = User::factory()->admin()->create();
        $conversation = AiChatConversation::factory()->create();
        $policy = new AiChatConversationPolicy;

        $this->assertFalse($policy->viewAny($coach));
        $this->assertFalse($policy->view($coach, $conversation));
        $this->assertFalse($policy->update($coach, $conversation));
        $this->assertFalse($policy->delete($coach, $conversation));

        $this->assertFalse($policy->viewAny($admin));
        $this->assertFalse($policy->view($admin, $conversation));
        $this->assertFalse($policy->update($admin, $conversation));
        $this->assertFalse($policy->delete($admin, $conversation));
    }
}
