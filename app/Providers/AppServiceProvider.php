<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Schema;

use App\Services\PaymentGatewayManager;
use App\Plugins\bKashDriver;
use App\Plugins\NagadDriver;
use App\Plugins\UpayDriver;
use App\Plugins\BinanceDriver;
use App\Plugins\BybitDriver;
use App\Plugins\Web3Driver;
use App\Plugins\RocketDriver;
use App\Plugins\CellfinDriver;
use App\Plugins\OkWalletDriver;
use App\Plugins\TapDriver;

use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            $manager = new PaymentGatewayManager();
            $manager->registerDriver(new bKashDriver());
            $manager->registerDriver(new NagadDriver());
            $manager->registerDriver(new UpayDriver());
            $manager->registerDriver(new BinanceDriver());
            $manager->registerDriver(new BybitDriver());
            $manager->registerDriver(new Web3Driver());
            $manager->registerDriver(new RocketDriver());
            $manager->registerDriver(new CellfinDriver());
            $manager->registerDriver(new OkWalletDriver());
            $manager->registerDriver(new TapDriver());
            return $manager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        try {
            if (Schema::hasTable('settings')) {
                config([
                    'mail.mailers.smtp.host' => \App\Models\Setting::get('mail_host', config('mail.mailers.smtp.host')),
                    'mail.mailers.smtp.port' => \App\Models\Setting::get('mail_port', config('mail.mailers.smtp.port')),
                    'mail.mailers.smtp.username' => \App\Models\Setting::get('mail_username', config('mail.mailers.smtp.username')),
                    'mail.mailers.smtp.password' => \App\Models\Setting::get('mail_password', config('mail.mailers.smtp.password')),
                    'mail.mailers.smtp.encryption' => \App\Models\Setting::get('mail_encryption', config('mail.mailers.smtp.encryption')),
                    'mail.from.address' => \App\Models\Setting::get('mail_from_address', config('mail.from.address')),
                    'mail.from.name' => \App\Models\Setting::get('mail_from_name', config('mail.from.name')),
                ]);
            }
        } catch (\Exception $e) {
            // Ignore database connection issues during migration runs
        }

        Gate::define('admin', function ($user) {
            return $user->role === 'admin';
        });
    }
}
