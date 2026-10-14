<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Models\Enrollment;
use App\Services\MeetingAvailabilityService;
use Carbon\Carbon;

final class FetchAvailabilityAction
{
    public function __construct(
        private readonly MeetingAvailabilityService $availabilityService,
    ) {}

    public function __invoke(
        Enrollment $enrollment,
        string $date,
    ): array {
        $date = Carbon::parse($date);

        $slots = $this->availabilityService->slotsForCertification(
            $enrollment->loadMissing('certification')->certification,
            $date,
        );

        return [
            'date' => $date,
            'slots' => $slots,
        ];
    }
}
