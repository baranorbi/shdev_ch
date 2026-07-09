<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_json_booking_request_returns_consistent_validation_errors(): void
    {
        $response = $this->postJson('/bookings', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'date' => Carbon::now()->next(Carbon::SATURDAY)->toDateString(),
            'hour' => '10:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['date'],
            ])
            ->assertJsonPath('message', 'Validation failed.');
    }

    public function test_json_booking_request_creates_booking_with_service_layer(): void
    {
        $response = $this->postJson('/bookings', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'date' => Carbon::now()->next(Carbon::MONDAY)->toDateString(),
            'hour' => '10:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Booking confirmed! We look forward to seeing you.')
            ->assertJsonPath('data.booking.name', 'Jane Doe')
            ->assertJsonPath('data.booking.email', 'jane@example.com');

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseHas('bookings', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    }
}