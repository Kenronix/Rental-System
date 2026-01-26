<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::firstOrCreate(
            ['email' => 'kennethjamesbatuhan734@gmail.com'],
            [
                'name' => 'kenneth batuhan',
                'password' => Hash::make('12345678'),
                'phone' => '09293204854',
                'address' => 'Cebu city',
            ]
        );

        $this->command->info('Tenant account created successfully!');
        $this->command->info('Email: kennethjamesbatuhan734@gmail.com');
        $this->command->info('Password: 12345678');
    }
}