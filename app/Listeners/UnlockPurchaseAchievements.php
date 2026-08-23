<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Events\PurchaseCompleted;

class UnlockPurchaseAchievements
{
    public function handle(PurchaseCompleted $event): void
    {
        $count = $event->user->purchases()->count();

        foreach (config('achievements.groups.purchases', []) as $rule) {
            if ($count < $rule['threshold']) {
                continue;
            }

            $unlock = $event->user->achievementUnlocks()->firstOrCreate(
                ['achievement_code' => $rule['code']],
                [
                    'achievement_name' => $rule['name'],
                    'group' => 'purchases',
                    'unlocked_at' => now(),
                ],
            );

            if ($unlock->wasRecentlyCreated) {
                AchievementUnlocked::dispatch($rule['name'], $event->user);
            }
        }
    }
}
