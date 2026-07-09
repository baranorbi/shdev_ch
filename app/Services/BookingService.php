<?php

namespace App\Services;

use App\Data\BookingData;
use App\Models\Booking;

class BookingService
{
    public function create(BookingData $data): Booking
    {
        return Booking::create([
            'name' => $data->name,
            'email' => $data->email,
            'scheduled_at' => $data->scheduledAt,
        ]);
    }
}