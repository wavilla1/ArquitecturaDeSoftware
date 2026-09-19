<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('type')->default('fixed')->after('price');
            $table->timestamp('closes_at')->nullable()->after('type');
            $table->unsignedBigInteger('winning_bid_id')->nullable()->after('closes_at');
        });

        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bidder_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->foreign('winning_bid_id')->references('id')->on('bids')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropForeign(['winning_bid_id']);
        });

        Schema::dropIfExists('bids');

        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['type', 'closes_at', 'winning_bid_id']);
        });
    }
};
