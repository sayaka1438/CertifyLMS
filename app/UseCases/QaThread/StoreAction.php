<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Models\QaThread;
use App\Models\User;

final class StoreAction
{
    /**
     * @param array{
     *     certification_id: string,
     *     title: string,
     *     body: string,
     * } $validated
     */
    public function __invoke(User $user, array $validated): QaThread
    {
        return QaThread::create([
            'user_id' => $user->id,
            'certification_id' => $validated['certification_id'],
            'title' => $validated['title'],
            'body' => $validated['body'],
        ]);
    }
}
