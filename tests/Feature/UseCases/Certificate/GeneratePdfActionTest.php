<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Certificate;

use App\Exceptions\Certification\CertificatePdfGenerationException;
use App\Models\Certificate;
use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\User;
use App\UseCases\Certificate\GeneratePdfAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GeneratePdfActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_and_stores_certificate_pdf(): void
    {
        Storage::fake('private');

        $student = User::factory()->student()->create();
        $certification = Certification::factory()->create();

        $enrollment = Enrollment::factory()
            ->for($student)
            ->for($certification)
            ->create();

        $certificate = Certificate::factory()
            ->forEnrollment($enrollment)
            ->create();

        app(GeneratePdfAction::class)($certificate);

        Storage::disk('private')->assertExists($certificate->pdf_path);
    }

    public function test_throws_exception_when_pdf_storage_fails(): void
    {
        $student = User::factory()->student()->create();
        $certification = Certification::factory()->create();

        $enrollment = Enrollment::factory()
            ->for($student)
            ->for($certification)
            ->create();

        $certificate = Certificate::factory()
            ->forEnrollment($enrollment)
            ->create();

        Storage::shouldReceive('disk')
            ->with('private')
            ->andReturnSelf();

        Storage::shouldReceive('put')
            ->with($certificate->pdf_path, \Mockery::type('string'))
            ->once()
            ->andReturnFalse();

        Storage::shouldReceive('delete')
            ->with($certificate->pdf_path)
            ->once()
            ->andReturnTrue();

        $this->expectException(CertificatePdfGenerationException::class);

        app(GeneratePdfAction::class)($certificate);
    }
}
