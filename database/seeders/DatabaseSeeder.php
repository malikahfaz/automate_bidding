<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PlatformAccount;
use App\Models\Auction;
use App\Models\BidHistory;
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

        // Ivalua
        $ivaluaAccount = PlatformAccount::updateOrCreate(
            ['platform' => 'ivalua'],
            [
                'email' => 'sales_ivalua@ellectmobility.com',
                'encrypted_password' => \Illuminate\Support\Facades\Crypt::encryptString('Ellect123%'),
                'status' => 'active',
            ]
        );

        // 4. Create Mock Featured Auctions
        $auction1 = Auction::updateOrCreate(
            ['external_url' => 'https://t-mobile.ivalua.app/page.aspx/en/auc/auction_browse_extranet?id=101'],
            [
                'platform' => 'ivalua',
                'title' => 'T-Mobile Ivalua Auction: Apple iPhone 15 Pro Max Bulk Lot (50 units)',
                'current_bid' => 24500.00,
                'bid_increment' => 250.00,
                'time_remaining' => '2h 14m',
                'ends_at' => now()->addHours(2)->addMinutes(14),
                'status' => 'active',
                'is_active' => true,
                'is_featured' => true,
                'last_synced_at' => now(),
            ]
        );

        $auction2 = Auction::updateOrCreate(
            ['external_url' => 'https://bids.ellectmobility.com/storefront/bstock?id=4520'],
            [
                'platform' => 'bstock',
                'title' => 'B-Stock: Grade A Samsung Galaxy S24 Ultra Mixed Lot (25 units)',
                'current_bid' => 12100.00,
                'bid_increment' => 100.00,
                'time_remaining' => '4h 45m',
                'ends_at' => now()->addHours(4)->addMinutes(45),
                'status' => 'active',
                'is_active' => true,
                'is_featured' => true,
                'last_synced_at' => now(),
            ]
        );

        $auction3 = Auction::updateOrCreate(
            ['external_url' => 'https://bids.ellectmobility.com/storefront/bstock?id=3102'],
            [
                'platform' => 'bstock',
                'title' => 'B-Stock: Target Customer Returns - Electronics & Mobile Accessories (Pallet)',
                'current_bid' => 3400.00,
                'bid_increment' => 50.00,
                'time_remaining' => '1d 6h',
                'ends_at' => now()->addDay()->addHours(6),
                'status' => 'active',
                'is_active' => true,
                'is_featured' => false,
                'last_synced_at' => now(),
            ]
        );

        // Seed some history for the mockup auctions
        BidHistory::updateOrCreate(
            ['auction_id' => $auction1->id, 'amount' => 24500.00],
            ['source' => 'external', 'status' => 'successful', 'created_at' => now()->subMinutes(10)]
        );
        BidHistory::updateOrCreate(
            ['auction_id' => $auction1->id, 'amount' => 24250.00],
            ['source' => 'external', 'status' => 'successful', 'created_at' => now()->subMinutes(20)]
        );

        BidHistory::updateOrCreate(
            ['auction_id' => $auction2->id, 'amount' => 12100.00],
            ['source' => 'external', 'status' => 'successful', 'created_at' => now()->subMinutes(5)]
        );
        BidHistory::updateOrCreate(
            ['auction_id' => $auction2->id, 'amount' => 12000.00],
            ['source' => 'external', 'status' => 'successful', 'created_at' => now()->subMinutes(15)]
        );
    }
}
