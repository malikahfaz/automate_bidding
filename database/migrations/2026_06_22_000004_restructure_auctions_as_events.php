<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->string('external_event_id')->nullable()->after('platform');
            $table->string('auction_group')->nullable()->after('external_event_id');
            $table->text('browse_url')->nullable()->after('external_url');
            $table->unsignedInteger('lots_count')->default(0)->after('title');
            $table->dateTime('starts_at')->nullable()->after('ends_at');
        });

        // Migrate existing lot-level rows into auction_lots + normalize parent auctions
        if (Schema::hasColumn('auctions', 'external_lot_id')) {
            $legacyLots = DB::table('auctions')
                ->whereNotNull('external_lot_id')
                ->get();

            $eventMap = [];

            foreach ($legacyLots as $row) {
                $eventKey = $row->external_url;

                if (!isset($eventMap[$eventKey])) {
                    $eventId = null;
                    if (preg_match('/auction_console\/(\d+)/', $row->external_url, $m)) {
                        $eventId = $m[1];
                    }

                    $parentId = DB::table('auctions')->insertGetId([
                        'platform' => $row->platform,
                        'external_event_id' => $eventId,
                        'auction_group' => $eventId ? 'AET' . substr($eventId, -1) : null,
                        'external_url' => $row->external_url,
                        'browse_url' => 'https://t-mobile.ivalua.app/page.aspx/en/auc/auction_browse_extranet',
                        'title' => 'Auction Event ' . ($eventId ?? ''),
                        'lots_count' => 0,
                        'current_bid' => 0,
                        'bid_increment' => 0,
                        'status' => $row->status,
                        'is_active' => $row->is_active,
                        'is_featured' => $row->is_featured,
                        'starts_at' => null,
                        'ends_at' => $row->ends_at,
                        'last_synced_at' => $row->last_synced_at,
                        'last_sync_error' => $row->last_sync_error,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $eventMap[$eventKey] = $parentId;
                }

                $parentId = $eventMap[$eventKey];

                $lotId = DB::table('auction_lots')->insertGetId([
                    'auction_id' => $parentId,
                    'external_lot_id' => $row->external_lot_id,
                    'title' => $row->title ?? $row->external_lot_id,
                    'current_bid' => $row->current_bid,
                    'bid_increment' => $row->bid_increment,
                    'time_remaining' => $row->time_remaining,
                    'ends_at' => $row->ends_at,
                    'status' => $row->status,
                    'is_active' => $row->is_active,
                    'last_synced_at' => $row->last_synced_at,
                    'last_sync_error' => $row->last_sync_error,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);

                DB::table('bid_histories')->where('auction_id', $row->id)->update([
                    'auction_lot_id' => $lotId,
                ]);
                DB::table('bids')->where('auction_id', $row->id)->update([
                    'auction_lot_id' => $lotId,
                    'auction_id' => $parentId,
                ]);
                DB::table('proxy_bids')->where('auction_id', $row->id)->update([
                    'auction_lot_id' => $lotId,
                    'auction_id' => $parentId,
                ]);

                DB::table('auctions')->where('id', $row->id)->delete();
            }

            foreach ($eventMap as $url => $parentId) {
                $count = DB::table('auction_lots')->where('auction_id', $parentId)->count();
                DB::table('auctions')->where('id', $parentId)->update(['lots_count' => $count]);
            }

            Schema::table('auctions', function (Blueprint $table) {
                $table->dropColumn('external_lot_id');
            });
        }

        // B-Stock / flat auctions: create one lot per auction if no lots exist
        $flatAuctions = DB::table('auctions')->get();
        foreach ($flatAuctions as $auction) {
            $hasLots = DB::table('auction_lots')->where('auction_id', $auction->id)->exists();
            if ($hasLots) {
                continue;
            }

            DB::table('auction_lots')->insert([
                'auction_id' => $auction->id,
                'external_lot_id' => 'LOT-' . $auction->id,
                'title' => $auction->title ?? 'Auction #' . $auction->id,
                'current_bid' => $auction->current_bid ?? 0,
                'bid_increment' => $auction->bid_increment ?? 0,
                'time_remaining' => $auction->time_remaining,
                'ends_at' => $auction->ends_at,
                'status' => $auction->status,
                'is_active' => $auction->is_active,
                'last_synced_at' => $auction->last_synced_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->string('external_lot_id')->nullable();
            $table->dropColumn(['external_event_id', 'auction_group', 'browse_url', 'lots_count', 'starts_at']);
        });
    }
};
