<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PlatformAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin
        User::updateOrCreate(
            ['email' => 'admin@ellectmobility.com'],
            [
                'name' => 'Ellect Admin',
                'password' => Hash::make('Password123!'),
                'role' => 'admin',
            ]
        );

        // 2. Create User
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('Password123!'),
                'role' => 'user',
            ]
        );

        // 3. Create Platform Accounts
        // B-Stock
        $bstockAccount = PlatformAccount::updateOrCreate(
            ['platform' => 'bstock'],
            [
                'email' => 'Sales@ellectmobility.com',
                'encrypted_password' => \Illuminate\Support\Facades\Crypt::encryptString('Ellect123%'),
                'status' => 'active',
            ]
        );

        // Ivalua — credentials loaded from .env via: php artisan platform:sync-credentials
        PlatformAccount::updateOrCreate(
            ['platform' => 'ivalua'],
            [
                'email' => env('IVALUA_MASTER_EMAIL', 'admin@example.com'),
                'encrypted_password' => \Illuminate\Support\Facades\Crypt::encryptString(env('IVALUA_MASTER_PASSWORD', 'changeme')),
                'status' => 'active',
            ]
        );

        // Live Ivalua lots are imported via: php artisan ivalua:import-auctions --purge-mock
    }
}
