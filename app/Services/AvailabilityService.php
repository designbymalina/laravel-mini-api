<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Holiday;
use Carbon\CarbonImmutable;

class AvailabilityService
{
    private const SLOT_DURATION = 30;

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

        $hours = $this->workingHours($date);

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

        $bookedSlots = Booking::query()
            ->whereDate('slot_start', $date->toDateString())
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

    /**
     * @return array{0:string,1:string}|null
     */
    private function workingHours(CarbonImmutable $date): ?array
    {
        if ($date->isSunday()) {
            return null;
        }

        if ($date->isSaturday()) {
            return [
                '10:00',
                '14:20'
            ];
        }

        return [
            '09:00',
            '17:00'
        ];
    }
}
