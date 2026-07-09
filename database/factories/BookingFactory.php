<?php

namespace Database\Factories;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $scheduledAt = $this->fakeScheduledAt();

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'scheduled_at' => $scheduledAt,
        ];
    }

    private function fakeScheduledAt(): Carbon
    {
        $day = Carbon::instance(fake()->dateTimeBetween('+1 week', '+6 weeks'));

        while ($day->isWeekend()) {
            $day = Carbon::instance(fake()->dateTimeBetween('+1 week', '+6 weeks'));
        }

        $hour = fake()->numberBetween(8, 16);

        return $day->setTime($hour, 0, 0);
    }
}
