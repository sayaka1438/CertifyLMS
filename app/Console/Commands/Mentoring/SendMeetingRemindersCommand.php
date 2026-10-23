<?php

declare(strict_types=1);

namespace App\Console\Commands\Mentoring;

use App\Enums\MeetingReminderWindow;
use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\UseCases\Meeting\SendMeetingReminderAction;
use Illuminate\Console\Command;
use Throwable;

class SendMeetingRemindersCommand extends Command
{
    protected $signature = 'notifications:send-meeting-reminders
                            {--window= : 通知タイミング（eve / one_hour_before）}';

    protected $description = '予約済み面談の前日・1時間前リマインダー通知を送信する';

    public function handle(SendMeetingReminderAction $action): int
    {
        $windowOption = $this->option('window');

        if (! is_string($windowOption)) {
            $this->error('--window オプションを指定してください。');

            return self::FAILURE;
        }

        $window = MeetingReminderWindow::tryFrom($windowOption);

        if ($window === null) {
            $this->error('--window には eve または one_hour_before を指定してください。');

            return self::FAILURE;
        }

        $now = now()->startOfMinute();

        $targetAt = match ($window) {
            MeetingReminderWindow::Eve => $now->copy()->addDay(),
            MeetingReminderWindow::OneHourBefore => $now->copy()->addHour(),
        };

        $targetFrom = $targetAt->copy()->subMinutes(5);
        $count = 0;
        $failedCount = 0;

        Meeting::query()
            ->where('status', MeetingStatus::Reserved->value)
            ->where('scheduled_at', '>=', $targetFrom)
            ->where('scheduled_at', '<=', $targetAt)
            ->orderBy('id')
            ->chunk(100, function ($meetings) use ($action, $window, &$count, &$failedCount): void {
                foreach ($meetings as $meeting) {
                    try {
                        $action($meeting, $window);
                        $count++;
                    } catch (Throwable $e) {
                        $failedCount++;

                        report($e);

                        $this->error(
                            "面談ID {$meeting->id} のリマインダー送信に失敗しました。"
                        );
                    }
                }
            });

        $this->info(
            "リマインダー対象の面談を {$count} 件処理しました。"
        );

        if ($failedCount > 0) {
            $this->warn(
                "{$failedCount} 件の面談でリマインダー送信に失敗しました。"
            );
        }

        return self::SUCCESS;
    }
}
