<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function create(string $name, string $email, CarbonImmutable $slotStart): Booking
    {
        $this->assertSlotCanBeBooked($slotStart);

        try {
            // @INFO Using transaction
            // return DB::transaction(
            //     function () use ($name, $email, $slotStart) {
            //         return Booking::create([
            //             'customer_name' => $name,
            //             'customer_email' => $email,
            //             'slot_start' => $slotStart,
            //             'slot_end' => $slotStart->addMinutes(30),
            //             'status' => BookingStatus::ACTIVE->value,
            //         ]);
            //     }
            // );

            $booking = new Booking();

            $booking->fill([
                'customer_name' => $name,
                'customer_email' => $email,
                'slot_start' => $slotStart,
                'slot_end' => $slotStart->addMinutes(30),
                'status' => BookingStatus::ACTIVE->value,
            ]);

            $booking->save();

            return $booking;
        } catch (QueryException $e) {
            throw ValidationException::withMessages([
                'slot' => [
                    'Slot already booked.'
                ]
            ]);
        }
    }

    private function assertSlotCanBeBooked(CarbonImmutable $slotStart): void
    {
        if ($slotStart->isSunday()) {
            throw ValidationException::withMessages([
                'slot' => [
                    'Bookings are unavailable on Sundays.'
                ]
            ]);
        }

        if (Holiday::query()
                ->whereDate(
                    'date',
                    $slotStart->toDateString()
                )
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'slot' => [
                    'Bookings are unavailable on holidays.'
                ]
            ]);
        }

        $hours = $this->workingHours($slotStart);

        if ($hours === null) {
            throw ValidationException::withMessages([
                'slot' => [
                    'Bookings are unavailable.'
                ]
            ]);
        }

        [$startTime, $endTime] = $hours;

        $workStart = CarbonImmutable::parse(
            $slotStart->format('Y-m-d') . ' ' . $startTime,
            'Europe/Warsaw'
        );

        $workEnd = CarbonImmutable::parse(
            $slotStart->format('Y-m-d') . ' ' . $endTime,
            'Europe/Warsaw'
        );

        $slotEnd = $slotStart->addMinutes(30);

        if (
            $slotStart < $workStart
            || $slotEnd > $workEnd
        ) {
            throw ValidationException::withMessages([
                'slot' => [
                    'Slot is outside working hours.'
                ]
            ]);
        }
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
            return ['10:00', '14:20'];
        }

        return ['09:00', '17:00'];
    }
}
