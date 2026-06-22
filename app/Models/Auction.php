<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Auction extends Model
{
    protected $fillable = [
        'platform',
        'external_event_id',
        'auction_group',
        'external_url',
        'browse_url',
        'title',
        'lots_count',
        'current_bid',
        'bid_increment',
        'time_remaining',
        'ends_at',
        'starts_at',
        'status',
        'is_active',
        'is_featured',
        'last_synced_at',
        'last_sync_error',
    ];

    protected $casts = [
        'current_bid' => 'decimal:2',
        'bid_increment' => 'decimal:2',
        'ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function lots(): HasMany
    {
        return $this->hasMany(AuctionLot::class);
    }

    public function activeLots(): HasMany
    {
        return $this->hasMany(AuctionLot::class)->where('is_active', true)->where('status', 'active');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /** @deprecated Use lots() — kept for legacy admin references */
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    /** @deprecated Use lots()->bidHistories */
    public function bidHistories(): HasMany
    {
        return $this->hasMany(BidHistory::class);
    }
}
