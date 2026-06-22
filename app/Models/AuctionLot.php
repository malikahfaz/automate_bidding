<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuctionLot extends Model
{
    protected $fillable = [
        'auction_id',
        'external_lot_id',
        'title',
        'current_bid',
        'bid_increment',
        'time_remaining',
        'ends_at',
        'status',
        'is_active',
        'last_synced_at',
        'last_sync_error',
    ];

    protected $casts = [
        'current_bid' => 'decimal:2',
        'bid_increment' => 'decimal:2',
        'ends_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    public function bidHistories(): HasMany
    {
        return $this->hasMany(BidHistory::class);
    }

    public function proxyBids(): HasMany
    {
        return $this->hasMany(ProxyBid::class);
    }

    public function activeProxyBid()
    {
        return $this->hasOne(ProxyBid::class)->where('status', 'active');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }
}
