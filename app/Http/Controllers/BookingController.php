<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Data\BookingData;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService)
    {
    }

    /**
     * Display the booking form.
     */
    public function index(): View
    {
        return view('bookings.index');
    }

    /**
     * Store a new booking.
     */
    public function store(BookingRequest $request): JsonResponse|RedirectResponse
    {
        $booking = $this->bookingService->create(
            BookingData::fromValidated($request->validated())
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Booking confirmed! We look forward to seeing you.',
                'data' => [
                    'booking' => [
                        'id' => $booking->id,
                        'name' => $booking->name,
                        'email' => $booking->email,
                        'scheduled_at' => $booking->scheduled_at->toIso8601String(),
                    ],
                ],
            ], 201);
        }

        return redirect()->route('bookings.index')
            ->with('success', 'Booking confirmed! We look forward to seeing you.');
    }
}
