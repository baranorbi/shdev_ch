<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HairdresserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hairdressers = [
            ['name' => 'Hairdresser Admin', 'email' => 'hairdresser@example.com'],
            ['name' => 'Stylist One', 'email' => 'stylist1@example.com'],
            ['name' => 'Stylist Two', 'email' => 'stylist2@example.com'],
        ];

        foreach ($hairdressers as $hairdresser) {
            User::updateOrCreate(
                ['email' => $hairdresser['email']],
                [
                    'name' => $hairdresser['name'],
                    'password' => Hash::make('password'),
                ]
            );
        }

        if ($this->command) {
            $this->command->info('Hairdresser users created successfully!');
            $this->command->info('Default password: password');
        }
    }
}
