<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProxyBid extends Model
{
    protected $fillable = [
        'auction_id',
        'user_id',
        'max_amount',
        'current_auto_bid',
        'status',
        'started_at',
        'stopped_at',
        'stop_reason',
    ];

    protected $casts = [
        'max_amount' => 'decimal:2',
        'current_auto_bid' => 'decimal:2',
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    /**
     * Get the auction.
     */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
