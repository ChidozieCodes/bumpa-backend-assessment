<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_initial_progress_state(): void
    {
        $user = User::factory()->create();

        $this->getJson("/users/{$user->id}/achievements")
            ->assertOk()
            ->assertExactJson([
                'unlocked_achievements' => [],
                'next_available_achievements' => ['First Purchase'],
                'current_badge' => null,
                'next_badge' => 'Beginner',
                'remaining_to_unlock_next_badge' => 1,
            ]);
    }

    public function test_it_returns_only_the_next_achievement_in_each_group(): void
    {
        $user = User::factory()->create();
        $this->unlock($user, 'first-purchase', 'First Purchase');
        $this->unlock($user, '5-purchases', '5 Purchases');

        $this->getJson("/users/{$user->id}/achievements")
            ->assertOk()
            ->assertExactJson([
                'unlocked_achievements' => ['First Purchase', '5 Purchases'],
                'next_available_achievements' => ['10 Purchases'],
                'current_badge' => 'Beginner',
                'next_badge' => 'Intermediate',
                'remaining_to_unlock_next_badge' => 2,
            ]);
    }

    public function test_five_achievements_leave_three_to_advanced_badge(): void
    {
        $user = User::factory()->create();
        foreach (array_slice(config('achievements.groups.purchases'), 0, 5) as $rule) {
            $this->unlock($user, $rule['code'], $rule['name']);
        }

        $this->getJson("/users/{$user->id}/achievements")
            ->assertJsonPath('current_badge', 'Intermediate')
            ->assertJsonPath('next_badge', 'Advanced')
            ->assertJsonPath('remaining_to_unlock_next_badge', 3)
            ->assertJsonPath('next_available_achievements.0', '100 Purchases');
    }

    public function test_master_has_no_next_badge_or_achievement(): void
    {
        $user = User::factory()->create();
        foreach (config('achievements.groups.purchases') as $rule) {
            $this->unlock($user, $rule['code'], $rule['name']);
        }

        $this->getJson("/users/{$user->id}/achievements")
            ->assertJsonPath('current_badge', 'Master')
            ->assertJsonPath('next_badge', null)
            ->assertJsonPath('remaining_to_unlock_next_badge', 0)
            ->assertJsonPath('next_available_achievements', []);
    }

    private function unlock(User $user, string $code, string $name): void
    {
        $user->achievementUnlocks()->create([
            'achievement_code' => $code,
            'achievement_name' => $name,
            'group' => 'purchases',
            'unlocked_at' => now(),
        ]);
    }
}
