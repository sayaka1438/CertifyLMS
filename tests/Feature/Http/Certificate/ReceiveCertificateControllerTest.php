<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Certificate;

use App\Exceptions\Certification\CertificatePdfGenerationException;
use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\MockExam;
use App\Models\MockExamSession;
use App\Models\User;
use App\UseCases\Certificate\GeneratePdfAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ReceiveCertificateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_receive_certificate_and_pdf_is_generated(): void
    {
        Storage::fake('private');

        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();

        $enrollment = Enrollment::factory()
            ->for($student)
            ->for($certification)
            ->learning()
            ->create();

        $exam = MockExam::factory()
            ->for($certification)
            ->create([
                'is_published' => true,
            ]);

        MockExamSession::factory()
            ->for($enrollment)
            ->for($exam)
            ->create([
                'pass' => true,
            ]);

        $response = $this->actingAs($student)->post(route('enrollments.receiveCertificate', $enrollment));

        $certificate = $enrollment->refresh()->certificate;

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success', '修了証を発行しました。おめでとうございます！');

        $this->assertNotNull($certificate);

        Storage::disk('private')->assertExists($certificate->pdf_path);
    }

    public function test_returns_500_when_certificate_pdf_generation_fails(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();

        $enrollment = Enrollment::factory()
            ->for($student)
            ->for($certification)
            ->learning()
            ->create();

        $exam = MockExam::factory()
            ->for($certification)
            ->create([
                'is_published' => true,
            ]);

        MockExamSession::factory()
            ->for($enrollment)
            ->for($exam)
            ->create([
                'pass' => true,
            ]);

        $generatePdf = Mockery::mock(GeneratePdfAction::class);
        $generatePdf
            ->shouldReceive('__invoke')
            ->once()
            ->andThrow(new CertificatePdfGenerationException);

        $this->app->instance(GeneratePdfAction::class, $generatePdf);

        $response = $this->actingAs($student)->post(route('enrollments.receiveCertificate', $enrollment));

        $response->assertStatus(500);
        $response->assertSee('修了証の発行に失敗しました。');
        $response->assertSee('時間をおいて再度お試しください。');
    }
}
