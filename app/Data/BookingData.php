<?php

namespace App\Data;

use Carbon\Carbon;

final class BookingData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly Carbon $scheduledAt,
    ) {}

    /**
     * @param  array{name:string,email:string,date:string,hour:string}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            trim($validated['name']),
            strtolower(trim($validated['email'])),
            Carbon::createFromFormat('Y-m-d H:i', $validated['date'].' '.$validated['hour']),
        );
    }
}
