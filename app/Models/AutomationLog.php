<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationLog extends Model
{
    protected $fillable = [
        'platform',
        'auction_id',
        'user_id',
        'action',
        'status',
        'message',
        'payload',
        'screenshot_path',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Get the auction associated with this log.
     */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /**
     * Get the user associated with this log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
