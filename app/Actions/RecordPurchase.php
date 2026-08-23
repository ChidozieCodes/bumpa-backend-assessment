<?php

namespace App\Actions;

use App\Events\PurchaseCompleted;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordPurchase
{
    public function execute(User $user, int $amountKobo, string $reference): Purchase
    {
        $purchase = DB::transaction(
            fn () => $user->purchases()->create([
                'amount_kobo' => $amountKobo,
                'reference' => $reference,
            ]),
        );

        PurchaseCompleted::dispatch($user, $purchase);

        return $purchase;
    }
}
