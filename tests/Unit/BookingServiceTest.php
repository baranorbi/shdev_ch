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
        $service = new BookingService;
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

    public function test_it_prevents_double_booking_for_same_time_slot(): void
    {
        $service = new BookingService;
        $scheduledAt = Carbon::create(2026, 7, 13, 11, 0, 0);

        $service->create(new BookingData(
            'Jane Doe',
            'jane@example.com',
            $scheduledAt,
        ));

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $service->create(new BookingData(
                'John Smith',
                'john@example.com',
                $scheduledAt,
            ));
        } finally {
            $this->assertDatabaseCount('bookings', 1);
        }
    }

    public function test_it_converts_unique_constraint_violation_to_validation_exception_on_race_condition(): void
    {
        $service = new BookingService;
        $scheduledAt = Carbon::create(2026, 7, 13, 14, 0, 0);

        // Simulate a concurrent insert happening right before Booking::create executes
        \App\Models\Booking::creating(function () use ($scheduledAt) {
            static $triggered = false;
            if (! $triggered) {
                $triggered = true;
                \Illuminate\Support\Facades\DB::table('bookings')->insert([
                    'name' => 'Concurrent User',
                    'email' => 'concurrent@example.com',
                    'scheduled_at' => $scheduledAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        try {
            $service->create(new BookingData(
                'Jane Doe',
                'jane@example.com',
                $scheduledAt,
            ));
            $this->fail('Expected ValidationException was not thrown.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame(
                'This time slot is already booked. Please choose another time.',
                $e->errors()['hour'][0] ?? null
            );
        } finally {
            \App\Models\Booking::flushEventListeners();
        }
    }
}
