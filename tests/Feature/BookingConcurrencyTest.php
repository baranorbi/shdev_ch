<?php

namespace Tests\Feature;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_booking_requests_for_same_slot_prevent_double_booking(): void
    {
        $slotDate = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $firstResponse = $this->postJson('/bookings', [
            'name' => 'Alice Client',
            'email' => 'alice@example.com',
            'date' => $slotDate,
            'hour' => '10:00',
        ]);

        $firstResponse->assertCreated();

        $secondResponse = $this->postJson('/bookings', [
            'name' => 'Bob Client',
            'email' => 'bob@example.com',
            'date' => $slotDate,
            'hour' => '10:00',
        ]);

        $secondResponse->assertStatus(422)
            ->assertJsonPath('errors.hour.0', 'This time slot is already booked. Please choose another time.');

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseHas('bookings', [
            'email' => 'alice@example.com',
        ]);
    }

    public function test_race_condition_during_booking_creation_returns_422_json_validation_error(): void
    {
        $slotDate = Carbon::now()->next(Carbon::TUESDAY)->toDateString();
        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $slotDate.' 15:00');

        Booking::creating(function () use ($scheduledAt) {
            static $triggered = false;
            if (! $triggered) {
                $triggered = true;
                DB::table('bookings')->insert([
                    'name' => 'Race Winner',
                    'email' => 'winner@example.com',
                    'scheduled_at' => $scheduledAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        try {
            $response = $this->postJson('/bookings', [
                'name' => 'Race Loser',
                'email' => 'loser@example.com',
                'date' => $slotDate,
                'hour' => '15:00',
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('errors.hour.0', 'This time slot is already booked. Please choose another time.');
        } finally {
            Booking::flushEventListeners();
        }
    }
}
