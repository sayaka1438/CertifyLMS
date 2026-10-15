<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Chapter;
use App\Models\Enrollment;
use App\Models\Part;
use App\Models\Section;
use App\Models\SectionProgress;
use App\Services\Learning\ProgressService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 学習進捗集計 Service `ProgressService` の検証。
 * Section / Chapter / Part / 資格全体の完了数・完了率と、
 * 複数 Enrollment の Section 完了率一括集計を検証する。
 */
class ProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_summarize_calculates_progress_for_all_levels(): void
    {
        $enrollment = Enrollment::factory()->learning()->create();
        $certification = $enrollment->certification;

        $part = Part::factory()
            ->forCertification($certification)
            ->published()
            ->create();

        $chapter1 = Chapter::factory()
            ->forPart($part)
            ->published()
            ->create();

        $chapter2 = Chapter::factory()
            ->forPart($part)
            ->published()
            ->create();

        $section1 = Section::factory()
            ->forChapter($chapter1)
            ->published()
            ->create();

        $section2 = Section::factory()
            ->forChapter($chapter1)
            ->published()
            ->create();

        $section3 = Section::factory()
            ->forChapter($chapter2)
            ->published()
            ->create();

        Section::factory()
            ->forChapter($chapter2)
            ->published()
            ->create();

        foreach ([$section1, $section2, $section3] as $section) {
            SectionProgress::factory()
                ->forEnrollment($enrollment)
                ->forSection($section)
                ->create();
        }

        $summary = app(ProgressService::class)->summarize($enrollment);

        $this->assertSame(4, $summary->sectionsTotal);
        $this->assertSame(3, $summary->sectionsCompleted);
        $this->assertSame(0.75, $summary->sectionCompletionRatio);

        $this->assertSame(2, $summary->chaptersTotal);
        $this->assertSame(1, $summary->chaptersCompleted);
        $this->assertSame(0.5, $summary->chapterCompletionRatio);

        $this->assertSame(1, $summary->partsTotal);
        $this->assertSame(0, $summary->partsCompleted);
        $this->assertSame(0.0, $summary->partCompletionRatio);

        $this->assertSame(0.75, $summary->overallCompletionRatio);
    }

    public function test_summarize_returns_zero_when_no_published_contents_exist(): void
    {
        $enrollment = Enrollment::factory()->learning()->create();

        $summary = app(ProgressService::class)->summarize($enrollment);

        $this->assertSame(0, $summary->sectionsTotal);
        $this->assertSame(0, $summary->sectionsCompleted);
        $this->assertSame(0.0, $summary->sectionCompletionRatio);

        $this->assertSame(0, $summary->chaptersTotal);
        $this->assertSame(0, $summary->chaptersCompleted);
        $this->assertSame(0.0, $summary->chapterCompletionRatio);

        $this->assertSame(0, $summary->partsTotal);
        $this->assertSame(0, $summary->partsCompleted);
        $this->assertSame(0.0, $summary->partCompletionRatio);

        $this->assertSame(0.0, $summary->overallCompletionRatio);
    }

    public function test_batch_calculate_section_ratios_calculates_each_enrollment_progress(): void
    {
        $enrollment1 = Enrollment::factory()->learning()->create();
        $enrollment2 = Enrollment::factory()->learning()->create();

        $part1 = Part::factory()
            ->forCertification($enrollment1->certification)
            ->published()
            ->create();

        $chapter1 = Chapter::factory()
            ->forPart($part1)
            ->published()
            ->create();

        $sections1 = Section::factory()
            ->count(4)
            ->forChapter($chapter1)
            ->published()
            ->create();

        foreach ($sections1->take(2) as $section) {
            SectionProgress::factory()
                ->forEnrollment($enrollment1)
                ->forSection($section)
                ->create();
        }

        $part2 = Part::factory()
            ->forCertification($enrollment2->certification)
            ->published()
            ->create();

        $chapter2 = Chapter::factory()
            ->forPart($part2)
            ->published()
            ->create();

        $sections2 = Section::factory()
            ->count(4)
            ->forChapter($chapter2)
            ->published()
            ->create();

        foreach ($sections2->take(3) as $section) {
            SectionProgress::factory()
                ->forEnrollment($enrollment2)
                ->forSection($section)
                ->create();
        }

        $enrollments = new Collection([
            $enrollment1,
            $enrollment2,
        ]);

        $ratios = app(ProgressService::class)
            ->batchCalculateSectionRatios($enrollments);

        $this->assertSame(0.5, $ratios[$enrollment1->id]);
        $this->assertSame(0.75, $ratios[$enrollment2->id]);
    }
}
