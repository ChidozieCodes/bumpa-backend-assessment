<?php

namespace Tests\Feature;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Events\PurchaseCompleted;
use App\Listeners\SendBadgeCashback;
use App\Models\User;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PurchaseAchievementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_purchase_unlocks_an_achievement_with_the_required_event_payload(): void
    {
        Queue::fake();
        Event::fake([AchievementUnlocked::class, BadgeUnlocked::class]);
        $user = User::factory()->create();

        $this->postJson("/users/{$user->id}/purchases", [
            'amount_kobo' => 500000,
            'reference' => 'purchase-001',
        ])->assertCreated();

        $this->assertDatabaseHas('achievement_unlocks', [
            'user_id' => $user->id,
            'achievement_name' => 'First Purchase',
        ]);
        Event::assertDispatched(AchievementUnlocked::class, fn ($event) => $event->achievement_name === 'First Purchase' && $event->user->is($user)
        );
    }

    public function test_fifth_purchase_unlocks_only_the_new_milestone(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        foreach (range(1, 5) as $number) {
            $this->postJson("/users/{$user->id}/purchases", [
                'amount_kobo' => 10000,
                'reference' => "purchase-{$number}",
            ])->assertCreated();
        }

        $this->assertSame(
            ['First Purchase', '5 Purchases'],
            $user->achievementUnlocks()->orderBy('id')->pluck('achievement_name')->all(),
        );
        $this->assertSame(['Beginner'], $user->badgeUnlocks()->pluck('badge_name')->all());
        Queue::assertPushed(CallQueuedListener::class, fn (CallQueuedListener $job) => $job->class === SendBadgeCashback::class
        );
    }

    public function test_replaying_purchase_event_does_not_duplicate_unlocks(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        foreach (range(1, 5) as $number) {
            $purchase = $user->purchases()->create([
                'amount_kobo' => 10000,
                'reference' => "replay-{$number}",
            ]);
        }

        event(new PurchaseCompleted($user, $purchase));
        event(new PurchaseCompleted($user, $purchase));

        $this->assertSame(2, $user->achievementUnlocks()->count());
        $this->assertSame(1, $user->badgeUnlocks()->count());
    }

    public function test_purchase_input_is_validated_and_reference_is_unique(): void
    {
        $user = User::factory()->create();

        $this->postJson("/users/{$user->id}/purchases", [
            'amount_kobo' => 99,
            'reference' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors(['amount_kobo', 'reference']);

        $payload = ['amount_kobo' => 10000, 'reference' => 'duplicate-reference'];
        $this->postJson("/users/{$user->id}/purchases", $payload)->assertCreated();
        $this->postJson("/users/{$user->id}/purchases", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reference');
    }
}
