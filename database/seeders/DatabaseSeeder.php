<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@omnipay.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // 2. Seed Payment Methods
        $methods = [
            ['name' => 'bKash (MFS)', 'code' => 'bkash'],
            ['name' => 'Nagad (MFS)', 'code' => 'nagad'],
            ['name' => 'Upay (MFS)', 'code' => 'upay'],
            ['name' => 'Binance Pay', 'code' => 'binance'],
            ['name' => 'Bybit Pay', 'code' => 'bybit'],
            ['name' => 'Web3 Crypto Wallet', 'code' => 'web3'],
            ['name' => 'Rocket (MFS)', 'code' => 'rocket'],
            ['name' => 'CellFin (MFS)', 'code' => 'cellfin'],
            ['name' => 'OK Wallet (MFS)', 'code' => 'okwallet'],
            ['name' => 'Tap (MFS)', 'code' => 'tap'],
            ['name' => 'Stripe', 'code' => 'stripe'],
            ['name' => 'PayPal', 'code' => 'paypal'],
            ['name' => 'Razorpay', 'code' => 'razorpay'],
        ];

        foreach ($methods as $m) {
            \App\Models\PaymentMethod::create($m);
        }
    }
}
