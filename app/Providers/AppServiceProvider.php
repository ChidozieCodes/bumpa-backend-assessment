<?php

namespace App\Providers;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Events\PurchaseCompleted;
use App\Listeners\SendBadgeCashback;
use App\Listeners\UnlockBadges;
use App\Listeners\UnlockPurchaseAchievements;
use App\Payments\CashbackGateway;
use App\Payments\LogCashbackGateway;
use App\Payments\PaystackCashbackGateway;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CashbackGateway::class, fn ($app) => match (config('payments.driver')) {
            'paystack' => $app->make(PaystackCashbackGateway::class),
            default => $app->make(LogCashbackGateway::class),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(PurchaseCompleted::class, UnlockPurchaseAchievements::class);
        Event::listen(AchievementUnlocked::class, UnlockBadges::class);
        Event::listen(BadgeUnlocked::class, SendBadgeCashback::class);
    }
}
