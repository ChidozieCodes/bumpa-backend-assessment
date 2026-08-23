<?php

namespace Tests\Unit;

use App\Events\BadgeUnlocked;
use App\Listeners\SendBadgeCashback;
use App\Models\BadgeUnlock;
use App\Models\User;
use App\Payments\CashbackGateway;
use App\Payments\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendBadgeCashbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_exactly_300_naira_once_for_a_badge(): void
    {
        $user = User::factory()->create();
        $badge = BadgeUnlock::create([
            'user_id' => $user->id,
            'badge_code' => 'beginner',
            'badge_name' => 'Beginner',
            'unlocked_at' => now(),
        ]);
        $gateway = Mockery::mock(CashbackGateway::class);
        $gateway->shouldReceive('provider')->once()->andReturn('fake');
        $gateway->shouldReceive('send')
            ->once()
            ->withArgs(fn ($recipient, $amount, $reference) => $recipient->is($user) && $amount === 30000 && str_starts_with($reference, 'badge-')
            )
            ->andReturn(new PaymentResult('provider-transfer-1'));

        $listener = new SendBadgeCashback($gateway);
        $event = new BadgeUnlocked('Beginner', $user, $badge);
        $listener->handle($event);
        $listener->handle($event);

        $this->assertDatabaseCount('cashbacks', 1);
        $this->assertDatabaseHas('cashbacks', [
            'badge_unlock_id' => $badge->id,
            'amount_kobo' => 30000,
            'status' => 'succeeded',
            'provider_reference' => 'provider-transfer-1',
        ]);
    }

    public function test_a_failed_payment_is_recorded_and_rethrown_for_queue_retry(): void
    {
        $this->expectExceptionMessage('Provider unavailable');
        $user = User::factory()->create();
        $badge = BadgeUnlock::create([
            'user_id' => $user->id,
            'badge_code' => 'beginner',
            'badge_name' => 'Beginner',
            'unlocked_at' => now(),
        ]);
        $gateway = Mockery::mock(CashbackGateway::class);
        $gateway->shouldReceive('provider')->once()->andReturn('fake');
        $gateway->shouldReceive('send')->once()->andThrow(new \RuntimeException('Provider unavailable'));

        try {
            (new SendBadgeCashback($gateway))->handle(new BadgeUnlocked('Beginner', $user, $badge));
        } finally {
            $this->assertDatabaseHas('cashbacks', [
                'badge_unlock_id' => $badge->id,
                'status' => 'failed',
                'failure_reason' => 'Provider unavailable',
            ]);
        }
    }
}
