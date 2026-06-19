<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // bstock, ivalua
            $table->text('external_url');
            $table->string('title')->nullable();
            $table->decimal('current_bid', 12, 2)->default(0.00);
            $table->decimal('bid_increment', 12, 2)->default(0.00);
            $table->string('time_remaining')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('status')->default('active'); // active, ended, paused, failed
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
