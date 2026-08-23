<?php

namespace App\Listeners;

use App\Events\BadgeUnlocked;
use App\Models\Cashback;
use App\Payments\CashbackGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SendBadgeCashback implements ShouldQueue
{
    public int $tries = 5;

    public function __construct(private CashbackGateway $gateway) {}

    public function handle(BadgeUnlocked $event): void
    {
        $reference = sprintf('badge-%d-user-%d', $event->badgeUnlock->id, $event->user->id);
        $cashback = Cashback::firstOrNew(['badge_unlock_id' => $event->badgeUnlock->id]);

        if (! $cashback->exists) {
            $cashback->fill([
                'user_id' => $event->user->id,
                'amount_kobo' => config('achievements.cashback_amount_kobo'),
                'reference' => $reference,
                'provider' => $this->gateway->provider(),
                'status' => 'pending',
            ])->save();
        }

        if ($cashback->status === 'succeeded') {
            return;
        }

        try {
            $result = $this->gateway->send($event->user, $cashback->amount_kobo, $cashback->reference);
            $cashback->update([
                'status' => 'succeeded',
                'provider_reference' => $result->providerReference,
                'failure_reason' => null,
            ]);
        } catch (Throwable $exception) {
            $cashback->update(['status' => 'failed', 'failure_reason' => $exception->getMessage()]);
            throw $exception;
        }
    }
}
