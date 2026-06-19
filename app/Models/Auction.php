<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Auction extends Model
{
    protected $fillable = [
        'platform',
        'external_url',
        'title',
        'current_bid',
        'bid_increment',
        'time_remaining',
        'ends_at',
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
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Get bids submitted on this auction.
     */
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get proxy bid configurations.
     */
    public function proxyBids(): HasMany
    {
        return $this->hasMany(ProxyBid::class);
    }

    /**
     * Active proxy bid on this auction (usually only one active proxy bid per auction).
     */
    public function activeProxyBid()
    {
        return $this->hasOne(ProxyBid::class)->where('status', 'active');
    }

    /**
     * Get bid history values.
     */
    public function bidHistories(): HasMany
    {
        return $this->hasMany(BidHistory::class)->orderBy('amount', 'desc');
    }

    /**
     * Scope for active auctions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    /**
     * Scope for featured auctions.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
