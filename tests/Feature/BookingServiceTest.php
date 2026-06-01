<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_booking(): void
    {
        $service = app(BookingService::class);

        $booking = $service->create(
            'Jan Kowalski',
            'jan@test.pl',
            CarbonImmutable::parse(
                '2026-06-08 09:00:00',
                'Europe/Warsaw'
            )
        );

        $this->assertDatabaseHas(
            'bookings',
            [
                'id' => $booking->id,
            ]
        );
    }

    public function test_cannot_create_two_active_bookings_for_same_slot(): void
    {
        $service = app(BookingService::class);

        $slot = CarbonImmutable::parse(
            '2026-06-08 09:00:00',
            'Europe/Warsaw'
        );

        $service->create(
            'Jan',
            'jan@test.pl',
            $slot
        );

        $this->expectException(
            ValidationException::class
        );

        $service->create(
            'Anna',
            'anna@test.pl',
            $slot
        );
    }

    public function test_cannot_book_outside_working_hours(): void
    {
        $service = app(BookingService::class);

        $this->expectException(
            ValidationException::class
        );

        $service->create(
            'Jan',
            'jan@test.pl',
            CarbonImmutable::parse(
                '2026-06-06 14:00:00',
                'Europe/Warsaw'
            )
        );
    }

    public function test_cancelled_booking_releases_slot(): void
    {
        $booking = Booking::create([
            'customer_name' => 'Jan',
            'customer_email' => 'jan@test.pl',
            'slot_start' => '2026-06-08 09:00:00',
            'slot_end' => '2026-06-08 09:30:00',
            'status' => BookingStatus::ACTIVE->value,
        ]);

        $booking->update([
            'status' => BookingStatus::CANCELLED->value,
        ]);

        $service = app(BookingService::class);

        $newBooking = $service->create(
            'Anna',
            'anna@test.pl',
            CarbonImmutable::parse(
                '2026-06-08 09:00:00',
                'Europe/Warsaw'
            )
        );

        $this->assertNotNull(
            $newBooking->id
        );
    }
}
