<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->foreignId('auction_lot_id')->nullable()->after('auction_id')->constrained()->nullOnDelete();
        });

        Schema::table('proxy_bids', function (Blueprint $table) {
            $table->foreignId('auction_lot_id')->nullable()->after('auction_id')->constrained()->nullOnDelete();
        });

        Schema::table('bid_histories', function (Blueprint $table) {
            $table->foreignId('auction_lot_id')->nullable()->after('auction_id')->constrained()->nullOnDelete();
        });

        Schema::table('automation_logs', function (Blueprint $table) {
            $table->foreignId('auction_lot_id')->nullable()->after('auction_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('automation_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('auction_lot_id');
        });

        Schema::table('bid_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('auction_lot_id');
        });

        Schema::table('proxy_bids', function (Blueprint $table) {
            $table->dropConstrainedForeignId('auction_lot_id');
        });

        Schema::table('bids', function (Blueprint $table) {
            $table->dropConstrainedForeignId('auction_lot_id');
        });
    }
};
