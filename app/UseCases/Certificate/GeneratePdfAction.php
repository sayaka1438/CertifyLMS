<?php

declare(strict_types=1);

namespace App\UseCases\Certificate;

use App\Exceptions\Certification\CertificatePdfGenerationException;
use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use RuntimeException;
use Throwable;

class GeneratePdfAction
{
    public function __invoke(Certificate $certificate): void
    {
        $certificate->loadMissing([
            'user',
            'certification',
        ]);

        try {
            $html = view('certificates.pdf', [
                'certificate' => $certificate,
            ])->render();

            $mpdf = new Mpdf([
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
            ]);

            $mpdf->WriteHTML($html);

            $pdf = $mpdf->Output('', 'S');

            $stored = Storage::disk('private')->put(
                $certificate->pdf_path,
                $pdf,
            );

            if (! $stored) {
                throw new RuntimeException('Failed to store certificate PDF.');
            }
        } catch (Throwable $e) {
            Storage::disk('private')->delete($certificate->pdf_path);

            throw new CertificatePdfGenerationException($e);
        }
    }
}
