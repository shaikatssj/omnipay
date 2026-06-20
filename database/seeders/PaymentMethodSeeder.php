<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
        ];

        foreach ($methods as $m) {
            PaymentMethod::firstOrCreate(
                ['code' => $m['code']],
                ['name' => $m['name'], 'is_active' => true]
            );
        }
    }
}
