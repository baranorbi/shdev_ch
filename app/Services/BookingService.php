<?php

namespace App\Services;

use App\Data\BookingData;
use App\Models\Booking;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Create a booking safely under concurrency.
     * Uses a database transaction with row locking and catches unique constraint violations
     * to prevent race conditions and overbooking.
     *
     * @throws ValidationException
     */
    public function create(BookingData $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $existingBooking = Booking::query()
                ->where('scheduled_at', $data->scheduledAt)
                ->lockForUpdate()
                ->first();

            if ($existingBooking !== null) {
                throw ValidationException::withMessages([
                    'hour' => 'This time slot is already booked. Please choose another time.',
                ]);
            }

            try {
                return Booking::create([
                    'name' => $data->name,
                    'email' => $data->email,
                    'scheduled_at' => $data->scheduledAt,
                ]);
            } catch (QueryException $exception) {
                if ($this->isUniqueConstraintViolation($exception)) {
                    throw ValidationException::withMessages([
                        'hour' => 'This time slot is already booked. Please choose another time.',
                    ]);
                }

                throw $exception;
            }
        }, 3);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $errorCode = $exception->errorInfo[1] ?? null;

        return in_array($sqlState, ['23000', '23505'], true)
            || in_array($errorCode, [1062, 19], true);
    }
}
