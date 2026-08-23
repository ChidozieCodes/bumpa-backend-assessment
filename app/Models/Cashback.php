<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cashback extends Model
{
    protected $fillable = [
        'user_id', 'badge_unlock_id', 'amount_kobo', 'reference', 'provider',
        'provider_reference', 'status', 'failure_reason',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badgeUnlock(): BelongsTo
    {
        return $this->belongsTo(BadgeUnlock::class);
    }
}
