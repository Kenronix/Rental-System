<?php

namespace Database\Seeders;

use App\Models\Landlord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LandlordSeeder extends Seeder
{

    public function run(): void
    {
        Landlord::firstOrCreate(
            ['email' => 'kennethbatuhan57@gmail.com'],
            [
                'name' => 'kenneth james batuhan',
                'password' => Hash::make('12345678'),
                'phone' => '09123456789',
                'address' => 'Cebu city',
            ]
        );

        $this->command->info('Landlord account created successfully!');
        $this->command->info('Email: kennethbatuhan57@gmail.com');
        $this->command->info('Password: 12345678');
    }
}
