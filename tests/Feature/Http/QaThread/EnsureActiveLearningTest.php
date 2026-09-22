<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureActiveLearningTest extends TestCase
{
    use RefreshDatabase;

    public function test_graduated_student_forbidden_on_index(): void
    {
        $student = User::factory()->student()->graduated()->create();

        $response = $this->actingAs($student)->get(route('qa-board.index'));

        $response->assertForbidden();
    }

    public function test_graduated_student_forbidden_on_show(): void
    {
        $student = User::factory()->student()->graduated()->create();

        $certification = Certification::factory()->published()->create();

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $response = $this->actingAs($student)->get(route('qa-board.show', $thread));

        $response->assertForbidden();
    }
}
