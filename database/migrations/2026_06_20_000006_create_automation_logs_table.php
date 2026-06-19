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
        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // bstock, ivalua
            $table->unsignedBigInteger('auction_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action'); // login, sync, bid
            $table->string('status')->default('info'); // info, success, warning, failed
            $table->text('message');
            $table->json('payload')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('platform');
            $table->index('status');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
    }
};
