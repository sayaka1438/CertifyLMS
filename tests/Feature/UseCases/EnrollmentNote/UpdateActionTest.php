<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\EnrollmentNote;

use App\Models\EnrollmentNote;
use App\UseCases\EnrollmentNote\UpdateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_note(): void
    {
        $note = EnrollmentNote::factory()->create();
        $validated = [
            'body' => '更新後の受講生メモです。',
        ];

        $result = app(UpdateAction::class)(
            note: $note,
            validated: $validated,
        );

        $this->assertSame($validated['body'], $result->body);
    }
}
