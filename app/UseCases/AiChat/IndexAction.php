<?php

declare(strict_types=1);

namespace App\UseCases\AiChat;

use App\Models\AiChatConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class IndexAction
{
    /**
     * @return Collection<int, AiChatConversation>
     */
    public function __invoke(User $user): Collection
    {
        return $user->aiChatConversations()
            ->with([
                'enrollment.certification',
                'section',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();
    }
}
