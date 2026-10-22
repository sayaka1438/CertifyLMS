<?php

declare(strict_types=1);

namespace App\UseCases\AiChat;

use App\Models\AiChatConversation;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;

final class StoreConversationAction
{
    /**
     * @param array{section_id?: string|null, message?: string|null} $validated
     */
    public function __invoke(User $user, array $validated): AiChatConversation
    {
        if (isset($validated['section_id'])) {
            $section = Section::query()
                ->with('chapter.part.certification')
                ->findOrFail($validated['section_id']);

            $enrollment = Enrollment::query()
                ->where('user_id', $user->id)
                ->where('certification_id', $section->chapter->part->certification->id)
                ->firstOrFail();

            $conversation = AiChatConversation::query()
                ->where('user_id', $user->id)
                ->where('section_id', $section->id)
                ->first();

            if ($conversation !== null) {
                return $conversation;
            }

            return AiChatConversation::create([
                'user_id' => $user->id,
                'enrollment_id' => $enrollment->id,
                'section_id' => $section->id,
                'title' => $section->title,
                'auto_title_enabled' => true,
            ]);
        }

        $enrollment = $user->defaultEnrollment;

        return AiChatConversation::create([
            'user_id' => $user->id,
            'enrollment_id' => $enrollment?->id,
            'section_id' => null,
            'title' => '新規相談',
            'auto_title_enabled' => true,
        ]);
    }
}
