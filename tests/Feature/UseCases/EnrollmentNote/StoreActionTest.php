<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\EnrollmentNote;

use App\Models\Enrollment;
use App\Models\User;
use App\UseCases\EnrollmentNote\StoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_note_for_enrollment(): void
    {
        $enrollment = Enrollment::factory()->create();
        $coach = User::factory()->coach()->create();
        $validated = [
            'body' => '学習状況を確認しました。順調に進んでいます。',
        ];

        $result = app(StoreAction::class)(
            enrollment: $enrollment,
            user: $coach,
            validated: $validated,
        );

        $this->assertSame($enrollment->id, $result->enrollment_id);
        $this->assertSame($coach->id, $result->created_by_user_id);
        $this->assertSame($validated['body'], $result->body);
    }
}
