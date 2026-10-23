<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CertificationStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\TermType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Certificate;
use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\EnrollmentStatusLog;
use App\Models\User;
use App\UseCases\Certificate\GeneratePdfAction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * 卒業生向けの修了証 + 過去 Enrollment 補完シーダー。
 *
 * EnrollmentSeeder が作成する既存の修了証はそのまま残し、
 * graduated 受講生 3 名に過去 Enrollment と Certificate を補完する。
 *
 * 修了証を割り当てる資格は名前で明示し、取得順に依存しない構成にする。
 *
 * 依存順序: UserSeeder → CertificationSeeder → EnrollmentSeeder → 本 Seeder。
 */
final class CertificateSeeder extends Seeder
{
    public function run(): void
    {
        $graduatedStudents = User::query()
            ->where('role', UserRole::Student->value)
            ->where('status', UserStatus::Graduated->value)
            ->orderBy('created_at')
            ->get();

        if ($graduatedStudents->isEmpty()) {
            $this->command?->info('CertificateSeeder: graduated 受講生が存在しないため補完なし。');

            return;
        }

        $certificationNames = [
            '基本情報技術者試験',
            '日商簿記 2 級',
            'TOEIC L&R 800 点コース',
        ];

        $certifications = Certification::query()
            ->where('status', CertificationStatus::Published->value)
            ->whereIn('name', $certificationNames)
            ->get()
            ->keyBy('name');

        foreach ($certificationNames as $name) {
            if (! $certifications->has($name)) {
                throw new RuntimeException(
                    "CertificateSeeder: 公開済資格「{$name}」が見つかりません。",
                );
            }
        }

        if ($graduatedStudents->count() < count($certificationNames)) {
            throw new RuntimeException(
                'CertificateSeeder: 修了証を割り当てる卒業済受講生が3名以上必要です。',
            );
        }

        foreach ($certificationNames as $i => $name) {
            $student = $graduatedStudents[$i];
            $certification = $certifications->get($name);

            if ($certification === null) {
                throw new RuntimeException(
                    "CertificateSeeder: 資格「{$name}」を取得できません。",
                );
            }

            $enrollment = $this->createPastEnrollment(
                $student,
                $certification,
                $i,
            );

            $this->issueCertificateForEnrollment($enrollment);
        }
    }

    /**
     * graduated 受講生に紐づく過去 Enrollment を作る。
     * passed 状態と試験日を設定し、EnrollmentStatusLog も記録する。
     */
    private function createPastEnrollment(
        User $student,
        Certification $certification,
        int $orderIndex,
    ): Enrollment {
        $planExpiresAt = $student->plan_expires_at
            ?? now()->subDays(30 + $orderIndex * 15);

        $examDate = $planExpiresAt->copy()->subDays(10)->toDateString();
        $passedAt = $planExpiresAt->copy()->subDays(7);
        $startedAt = $planExpiresAt->copy()->subDays(90);

        $enrollment = Enrollment::factory()
            ->for($student)
            ->for($certification)
            ->state([
                'status' => EnrollmentStatus::Passed->value,
                'current_term' => TermType::MockPractice->value,
                'exam_date' => $examDate,
                'passed_at' => $passedAt,
            ])
            ->create();

        $enrollment->forceFill([
            'created_at' => $startedAt,
            'updated_at' => $passedAt,
        ])->save();

        EnrollmentStatusLog::factory()->for($enrollment)->create([
            'from_status' => null,
            'to_status' => EnrollmentStatus::Learning->value,
            'changed_by_user_id' => $student->id,
            'changed_at' => $startedAt,
            'changed_reason' => '新規登録',
        ]);

        EnrollmentStatusLog::factory()->for($enrollment)->create([
            'from_status' => EnrollmentStatus::Learning->value,
            'to_status' => EnrollmentStatus::Passed->value,
            'changed_by_user_id' => $student->id,
            'changed_at' => $passedAt,
            'changed_reason' => '受講生による修了証受領',
        ]);

        return $enrollment;
    }

    /**
     * Certificate を発行し、PDF 実体も生成する。
     */
    private function issueCertificateForEnrollment(Enrollment $enrollment): void
    {
        $issuedAt = $enrollment->passed_at ?? now();

        $certificate = Certificate::factory()
            ->forEnrollment($enrollment)
            ->state([
                'pdf_path' => 'certificates/'.Str::ulid().'.pdf',
                'issued_at' => $issuedAt,
            ])
            ->create();

        app(GeneratePdfAction::class)($certificate);
    }
}
