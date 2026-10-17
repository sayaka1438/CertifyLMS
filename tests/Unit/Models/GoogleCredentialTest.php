<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\GoogleCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GoogleCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_relation_returns_owner_user(): void
    {
        $coach = User::factory()->coach()->create();

        $credential = GoogleCredential::factory()
            ->for($coach, 'user')
            ->create();

        $owner = $credential->user;

        $this->assertTrue($owner->is($coach));
    }

    public function test_datetime_casts_return_carbon_instances(): void
    {
        $credential = GoogleCredential::factory()->create();

        $fresh = $credential->fresh();

        $this->assertInstanceOf(Carbon::class, $fresh->expires_at);
        $this->assertInstanceOf(Carbon::class, $fresh->connected_at);
    }
}
