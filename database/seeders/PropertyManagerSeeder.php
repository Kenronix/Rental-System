<?php

namespace Database\Seeders;

use App\Models\PropertyManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PropertyManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PropertyManager::firstOrCreate(
            ['email' => 'manager@rentalsystem.com'],
            [
                'name' => 'System Manager',
                'password' => Hash::make('12345678'),
                'phone' => '09123456789',
                'role' => 'manager',
            ]
        );

        $this->command->info('Property Manager account created successfully!');
        $this->command->info('Email: manager@rentalsystem.com');
        $this->command->info('Password: 12345678');
    }
}
