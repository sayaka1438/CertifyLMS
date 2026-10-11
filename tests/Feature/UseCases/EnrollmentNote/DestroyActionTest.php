<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\EnrollmentNote;

use App\Models\EnrollmentNote;
use App\UseCases\EnrollmentNote\DestroyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_note(): void
    {
        $note = EnrollmentNote::factory()->create();

        $noteId = $note->id;

        app(DestroyAction::class)($note);

        $this->assertDatabaseMissing('enrollment_notes', ['id' => $noteId]);
    }
}
