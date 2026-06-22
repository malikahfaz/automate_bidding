<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_lots', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->unsignedInteger('quantity')->nullable()->after('description');
            $table->string('cosmetic_grade', 10)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('auction_lots', function (Blueprint $table) {
            $table->dropColumn(['description', 'quantity', 'cosmetic_grade']);
        });
    }
};
