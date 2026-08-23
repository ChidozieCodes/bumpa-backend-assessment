<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;

class UnlockBadges
{
    public function handle(AchievementUnlocked $event): void
    {
        $count = $event->user->achievementUnlocks()->count();

        foreach (config('achievements.badges', []) as $rule) {
            if ($count < $rule['threshold']) {
                continue;
            }

            $unlock = $event->user->badgeUnlocks()->firstOrCreate(
                ['badge_code' => $rule['code']],
                ['badge_name' => $rule['name'], 'unlocked_at' => now()],
            );

            if ($unlock->wasRecentlyCreated) {
                BadgeUnlocked::dispatch($rule['name'], $event->user, $unlock);
            }
        }
    }
}
