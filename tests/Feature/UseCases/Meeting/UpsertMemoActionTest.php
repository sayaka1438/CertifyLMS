<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Meeting;

use App\Models\Meeting;
use App\Models\MeetingMemo;
use App\UseCases\Meeting\UpsertMemoAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpsertMemoActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_meeting_memo(): void
    {
        $meeting = Meeting::factory()->reserved()->create();

        $body = '面談メモのテスト';

        app(UpsertMemoAction::class)(
            meeting: $meeting,
            body: $body,
        );

        $this->assertDatabaseHas('meeting_memos', [
            'meeting_id' => $meeting->id,
            'body' => $body,
        ]);
    }

    public function test_updates_existing_meeting_memo(): void
    {
        $meeting = Meeting::factory()->reserved()->create();

        MeetingMemo::factory()
            ->forMeeting($meeting)
            ->create([
                'body' => '更新前の面談メモ',
            ]);

        $body = '更新後の面談メモ';

        app(UpsertMemoAction::class)(
            meeting: $meeting,
            body: $body,
        );

        $this->assertDatabaseHas('meeting_memos', [
            'meeting_id' => $meeting->id,
            'body' => $body,
        ]);

        $this->assertDatabaseCount('meeting_memos', 1);
    }
}
