<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends Model
{
    protected $fillable = [
        'auction_id',
        'user_id',
        'bid_type',
        'amount',
        'max_amount',
        'status',
        'external_response',
        'failure_reason',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'external_response' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the auction this bid is for.
     */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /**
     * Get the user who placed the bid.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
