<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_relation_returns_owner_enrollment(): void
    {
        $enrollment = Enrollment::factory()->create();
        $note = EnrollmentNote::factory()->for($enrollment)->create();

        $owner = $note->enrollment;

        $this->assertTrue($owner->is($enrollment));
    }

    public function test_author_relation_returns_note_author(): void
    {
        $coach = User::factory()->coach()->create();
        $note = EnrollmentNote::factory()->create([
            'created_by_user_id' => $coach->id,
        ]);

        $author = $note->author;

        $this->assertTrue($author->is($coach));
    }
}
