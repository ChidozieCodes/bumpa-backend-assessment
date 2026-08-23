<?php

namespace App\Events;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user, public Purchase $purchase) {}
}
