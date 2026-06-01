<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Holiday;
use App\Support\WorkingHoursProvider;
use Carbon\CarbonImmutable;

class AvailabilityService
{
    private const SLOT_DURATION = 30;

    public function __construct(
        private readonly WorkingHoursProvider $workingHoursProvider,
    ) {
    }

    /**
     * @return array<int, array{
     *     start:string,
     *     end:string
     * }>
     */
    public function getAvailableSlots(CarbonImmutable $date): array
    {
        if (
            Holiday::query()
                ->whereDate('date', $date->toDateString())
                ->exists()
        ) {
            return [];
        }

        $hours = $this->workingHoursProvider->workingHours($date);

        if ($hours === null) {
            return [];
        }

        [$startTime, $endTime] = $hours;

        $start = CarbonImmutable::parse(
            $date->format('Y-m-d') . ' ' . $startTime,
            'Europe/Warsaw'
        );

        $end = CarbonImmutable::parse(
            $date->format('Y-m-d') . ' ' . $endTime,
            'Europe/Warsaw'
        );

        $startOfDay = $date->startOfDay();
        $endOfDay = $date->endOfDay();

        $bookedSlots = Booking::query()
            ->whereBetween('slot_start', [$startOfDay, $endOfDay])
            ->where('status', BookingStatus::ACTIVE->value)
            ->pluck('slot_start')
            ->map(
                fn ($slot): string => CarbonImmutable::parse($slot)
                    ->toIso8601String()
            )
            ->all();

        $bookedSlots = array_flip($bookedSlots);

        $slots = [];

        $current = $start;

        while (
            $current->addMinutes(self::SLOT_DURATION) <= $end
        ) {
            $key = $current->toIso8601String();

            if (! isset($bookedSlots[$key])) {
                $slots[] = [
                    'start' => $key,
                    'end' => $current
                        ->addMinutes(self::SLOT_DURATION)
                        ->toIso8601String(),
                ];
            }

            $current = $current->addMinutes(
                self::SLOT_DURATION
            );
        }

        return $slots;
    }
}
