<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Models\Certification;
use App\Models\QaThread;
use App\UseCases\QaThread\UpdateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_thread_title_and_body(): void
    {
        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create([
                'title' => '更新前のタイトル',
                'body' => '更新前の本文です。',
            ]);

        $validated = [
            'title' => '更新後のタイトル',
            'body' => '更新後の本文です。',
        ];

        $result = app(UpdateAction::class)($thread, $validated);

        $this->assertSame($validated['title'], $result->title);
        $this->assertSame($validated['body'], $result->body);
        $this->assertSame($certification->id, $result->certification_id);
    }
}
