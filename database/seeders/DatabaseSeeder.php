<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(HairdresserSeeder::class);

        Booking::query()->delete();

        Booking::factory()
            ->count(20)
            ->sequence(fn ($sequence) => [
                'scheduled_at' => now()->addWeekdays($sequence->index + 1)->setTime(9 + ($sequence->index % 8), 0),
            ])
            ->create();
    }
}
