<?php

namespace Tests\Unit;

use App\Data\BookingData;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_booking_from_dto(): void
    {
        $service = new BookingService();
        $scheduledAt = Carbon::create(2026, 7, 13, 11, 0, 0);

        $booking = $service->create(new BookingData(
            'Jane Doe',
            'jane@example.com',
            $scheduledAt,
        ));

        $this->assertSame('Jane Doe', $booking->name);
        $this->assertSame('jane@example.com', $booking->email);
        $this->assertTrue($scheduledAt->equalTo($booking->scheduled_at));
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    }
}