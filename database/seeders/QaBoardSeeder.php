<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class QaBoardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $student = User::query()
            ->where('email', 'student@certify-lms.test')
            ->first();

        if ($student === null) {
            return;
        }

        $students = User::query()
            ->where('role', UserRole::Student)
            ->where('status', UserStatus::InProgress)
            ->get();

        $otherStudents = $students->where('id', '!=', $student->id);

        if ($otherStudents->isEmpty()) {
            return;
        }

        $certifications = Certification::query()
            ->published()
            ->get();

        foreach ($certifications as $index => $certification) {
            $otherStudent = $otherStudents->random();

            $openThread = QaThread::factory()->create([
                'user_id' => $student->id,
                'certification_id' => $certification->id,
            ]);

            $createdAt = Carbon::now()->subDays($index + 1);

            $openThread->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();

            $resolvedThread = QaThread::factory()
                ->resolved()
                ->create([
                    'user_id' => $otherStudent->id,
                    'certification_id' => $certification->id,
                ]);

            $resolvedCreatedAt = Carbon::now()->subDays($index + 2);

            $resolvedThread->forceFill([
                'created_at' => $resolvedCreatedAt,
                'updated_at' => $resolvedCreatedAt,
                'resolved_at' => $resolvedCreatedAt->copy()->addDay(),
            ])->save();

            $coach = $certification->coaches()->first();

            if ($coach !== null) {
                $coachReply = QaReply::factory()->create([
                    'qa_thread_id' => $openThread->id,
                    'user_id' => $coach->id,
                ]);

                $coachReplyCreatedAt = $createdAt->copy()->addHour();

                $coachReply->forceFill([
                    'created_at' => $coachReplyCreatedAt,
                    'updated_at' => $coachReplyCreatedAt,
                ])->save();
            }

            $studentReply = QaReply::factory()->create([
                'qa_thread_id' => $openThread->id,
                'user_id' => $student->id,
            ]);

            $studentReplyCreatedAt = $createdAt->copy()->addHours(2);

            $studentReply->forceFill([
                'created_at' => $studentReplyCreatedAt,
                'updated_at' => $studentReplyCreatedAt,
            ])->save();
        }
    }
}
