<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// class BookingController extends Controller
// {
//     public function slots(Request $request)
//     {
//         $date = CarbonImmutable::parse(
//             $request->query('date')
//         );

//         return response()->json(
//             $availabilityService
//                 ->getAvailableSlots($date)
//         );
//     }

//     public function store(StoreBookingRequest $request)
//     {
//     }

//     public function cancel(Booking $booking)
//     {
//         $booking->update([
//             'status' => BookingStatus::CANCELLED
//         ]);

//         return response()->noContent();
//     }
// }

class BookingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly BookingService $bookingService,
    ) {
    }

    public function slots(Request $request): JsonResponse
    {
        $date = CarbonImmutable::parse(
            (string) $request->query('date')
        );

        return response()->json(
            $this->availabilityService->getAvailableSlots($date)
        );
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->create(
            $request->string('customer_name')->toString(),
            $request->string('customer_email')->toString(),
            CarbonImmutable::parse(
                $request->string('slot_start')->toString()
            )
        );

        return response()->json(
            $booking,
            Response::HTTP_CREATED
        );
    }

    public function cancel(Booking $booking): Response
    {
        $booking->update([
            'status' => BookingStatus::CANCELLED->value,
        ]);

        return response()->noContent();
    }
}
