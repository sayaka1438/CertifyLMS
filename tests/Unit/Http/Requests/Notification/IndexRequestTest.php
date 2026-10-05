<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndexRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_without_tab(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('notifications.index'));

        $response->assertSuccessful();
    }

    #[DataProvider('validTabs')]
    public function test_validation_passes_with_valid_tab(string $tab): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('notifications.index', [
            'tab' => $tab,
        ]));

        $response->assertSuccessful();
    }

    public function test_validation_fails_with_invalid_tab(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->getJson(route('notifications.index', [
            'tab' => 'unknown',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tab');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function validTabs(): array
    {
        return [
            'all' => ['all'],
            'unread' => ['unread'],
        ];
    }
}
