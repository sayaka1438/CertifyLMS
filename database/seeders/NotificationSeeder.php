<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChatRoom;
use App\Models\Meeting;
use App\Models\QaThread;
use App\Models\User;
use App\Notifications\ChatMessageReceivedNotification;
use App\Notifications\MeetingCanceledNotification;
use App\Notifications\MeetingReservedNotification;
use App\Notifications\QaReplyReceivedNotification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::query()
            ->where('email', 'student@certify-lms.test')
            ->first();

        $coach = User::query()
            ->where('email', 'coach@certify-lms.test')
            ->first();

        if ($student === null || $coach === null) {
            return;
        }

        $qaThread = QaThread::query()
            ->where('user_id', $student->id)
            ->first();

        $chatRoom = ChatRoom::query()
            ->forUser($student)
            ->forUser($coach)
            ->first();

        $meeting = Meeting::query()
            ->where('student_id', $student->id)
            ->where('coach_id', $coach->id)
            ->first();

        if ($qaThread === null || $chatRoom === null || $meeting === null) {
            return;
        }

        $this->createNotification(
            user: $student,
            type: QaReplyReceivedNotification::class,
            title: 'Q&Aに回答が届きました',
            message: "「{$qaThread->title}」に回答が投稿されました。",
            notificationType: 'qa_reply_received',
            url: route('qa-board.show', $qaThread),
        );

        $this->createNotification(
            user: $student,
            type: ChatMessageReceivedNotification::class,
            title: '新しいチャットメッセージが届きました',
            message: "{$coach->name}さんからメッセージが届きました。",
            notificationType: 'chat_message_received',
            url: route('chat.show', $chatRoom),
            readAt: now()->subHours(2),
        );

        $this->createNotification(
            user: $student,
            type: MeetingCanceledNotification::class,
            title: '面談がキャンセルされました。',
            message: "{$meeting->scheduled_at->format('Y年m月d日 H:i')}の面談がキャンセルされました。",
            notificationType: 'meeting_canceled',
            url: route('meetings.show', $meeting),
        );

        $this->createNotification(
            user: $coach,
            type: ChatMessageReceivedNotification::class,
            title: '新しいチャットメッセージが届きました',
            message: "{$student->name}さんからメッセージが届きました。",
            notificationType: 'chat_message_received',
            url: route('chat.show', $chatRoom),
        );

        $this->createNotification(
            user: $coach,
            type: MeetingReservedNotification::class,
            title: '面談が予約されました',
            message: "{$meeting->scheduled_at->format('Y年m月d日 H:i')}の面談が予約されました。",
            notificationType: 'meeting_reserved',
            url: route('meetings.show', $meeting),
            readAt: now()->subHours(3),
        );

        $this->createNotification(
            user: $coach,
            type: MeetingCanceledNotification::class,
            title: '面談がキャンセルされました。',
            message: "{$meeting->scheduled_at->format('Y年m月d日 H:i')}の面談がキャンセルされました。",
            notificationType: 'meeting_canceled',
            url: route('meetings.show', $meeting),
        );

        for ($i = 1; $i <= 18; $i++) {
            $this->createNotification(
                user: $student,
                type: ChatMessageReceivedNotification::class,
                title: '新しいチャットメッセージが届きました',
                message: "{$coach->name}さんからメッセージが届きました。",
                notificationType: 'chat_message_received',
                url: route('chat.show', $chatRoom),
                readAt: $i % 3 === 0 ? now()->subDays($i) : null,
            );
        }

        for ($i = 1; $i <= 18; $i++) {
            $this->createNotification(
                user: $coach,
                type: ChatMessageReceivedNotification::class,
                title: '新しいチャットメッセージが届きました',
                message: "{$student->name}さんからメッセージが届きました。",
                notificationType: 'chat_message_received',
                url: route('chat.show', $chatRoom),
                readAt: $i % 3 === 0 ? now()->subDays($i) : null,
            );
        }
    }

    private function createNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        string $notificationType,
        string $url,
        ?Carbon $readAt = null,
    ): void {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => $type,
            'data' => [
                'title' => $title,
                'message' => $message,
                'notification_type' => $notificationType,
                'url' => $url,
            ],
            'read_at' => $readAt,
        ]);
    }
}
