<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EnrollmentStatus;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;

class AiChatSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSectionConversation();
        $this->seedGeneralConversation();
    }

    private function seedSectionConversation(): void
    {
        $student = User::query()
            ->where('email', 'student@certify-lms.test')
            ->first();
        if ($student === null) {
            return;
        }

        $enrollment = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('status', EnrollmentStatus::Learning->value)
            ->orderBy('created_at')
            ->first();
        if ($enrollment === null) {
            return;
        }

        $section = Section::query()
            ->whereHas('chapter.part', fn ($q) => $q->where('certification_id', $enrollment->certification_id))
            ->first();
        if ($section === null) {
            return;
        }

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'section_id' => $section->id,
            'title' => "{$section->title}について質問",
        ]);

        AiChatMessage::factory()
            ->user()
            ->create([
                'ai_chat_conversation_id' => $conversation->id,
                'content' => 'この教材の内容について、もう少しわかりやすく説明してください。',
            ]);

        AiChatMessage::factory()
            ->assistant()
            ->create([
                'ai_chat_conversation_id' => $conversation->id,
                'content' => "「{$section->title}」について、ポイントを整理しながらわかりやすく説明します。",
            ]);
    }

    private function seedGeneralConversation(): void
    {
        $student = User::query()
            ->where('email', 'student@certify-lms.test')
            ->first();
        if ($student === null) {
            return;
        }

        $enrollment = $student->defaultEnrollment;

        $conversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'enrollment_id' => $enrollment?->id,
            'section_id' => null,
            'title' => '資格学習について相談',
        ]);

        AiChatMessage::factory()
            ->user()
            ->create([
                'ai_chat_conversation_id' => $conversation->id,
                'content' => 'この資格の勉強方法について教えてください。',
            ]);

        AiChatMessage::factory()
            ->assistant()
            ->create([
                'ai_chat_conversation_id' => $conversation->id,
                'content' => '資格学習を進める際は、学習範囲を整理して、計画的に復習を進めることが大切です。',
            ]);

        $errorConversation = AiChatConversation::factory()->create([
            'user_id' => $student->id,
            'enrollment_id' => $enrollment?->id,
            'section_id' => null,
            'title' => 'AI応答エラーの確認',
        ]);

        AiChatMessage::factory()
            ->user()
            ->create([
                'ai_chat_conversation_id' => $errorConversation->id,
                'content' => '試験直前はどのように勉強すればいいですか？',
            ]);

        AiChatMessage::factory()
            ->error()
            ->create([
                'ai_chat_conversation_id' => $errorConversation->id,
            ]);
    }
}
