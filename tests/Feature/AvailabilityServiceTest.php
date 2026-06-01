<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_no_slots_on_sunday(): void
    {
        $service = app(AvailabilityService::class);

        $slots = $service->getAvailableSlots(
            CarbonImmutable::parse('2026-06-07')
        );

        $this->assertSame([], $slots);
    }

    public function test_returns_no_slots_on_holiday(): void
    {
        Holiday::create([
            'date' => '2026-06-08',
            'name' => 'Holiday',
        ]);

        $service = app(AvailabilityService::class);

        $slots = $service->getAvailableSlots(
            CarbonImmutable::parse('2026-06-08')
        );

        $this->assertSame([], $slots);
    }

    public function test_returns_saturday_slots_that_fit_working_hours(): void
    {
        $service = app(AvailabilityService::class);

        $slots = $service->getAvailableSlots(
            CarbonImmutable::parse('2026-06-06')
        );

        $starts = array_column(
            $slots,
            'start'
        );

        $this->assertContains(
            '2026-06-06T13:30:00+02:00',
            $starts
        );

        $this->assertNotContains(
            '2026-06-06T14:00:00+02:00',
            $starts
        );
    }
}
