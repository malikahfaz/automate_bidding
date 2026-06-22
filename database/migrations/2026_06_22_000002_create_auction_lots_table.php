<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');
            $table->string('external_lot_id');
            $table->string('title');
            $table->decimal('current_bid', 12, 2)->default(0.00);
            $table->decimal('bid_increment', 12, 2)->default(0.00);
            $table->string('time_remaining')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('status')->default('active');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->timestamps();

            $table->unique(['auction_id', 'external_lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_lots');
    }
};
