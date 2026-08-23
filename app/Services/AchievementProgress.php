<?php

namespace App\Services;

use App\Models\User;

class AchievementProgress
{
    /** @return array<string, mixed> */
    public function for(User $user): array
    {
        $unlocked = $user->achievementUnlocks()->orderBy('id')->pluck('achievement_name')->all();
        $codes = $user->achievementUnlocks()->pluck('achievement_code')->all();
        $nextAchievements = [];

        foreach (config('achievements.groups', []) as $rules) {
            $next = collect($rules)->first(fn (array $rule) => ! in_array($rule['code'], $codes, true));
            if ($next) {
                $nextAchievements[] = $next['name'];
            }
        }

        $achievementCount = count($unlocked);
        $earnedBadges = collect(config('achievements.badges', []))
            ->filter(fn (array $badge) => $achievementCount >= $badge['threshold'])
            ->values();
        $current = $earnedBadges->last();
        $next = collect(config('achievements.badges', []))
            ->first(fn (array $badge) => $achievementCount < $badge['threshold']);

        return [
            'unlocked_achievements' => $unlocked,
            'next_available_achievements' => $nextAchievements,
            'current_badge' => $current['name'] ?? null,
            'next_badge' => $next['name'] ?? null,
            'remaining_to_unlock_next_badge' => $next ? $next['threshold'] - $achievementCount : 0,
        ];
    }
}
