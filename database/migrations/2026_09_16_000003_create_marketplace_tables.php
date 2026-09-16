<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->unsignedInteger('total_supply');
            $table->unsignedInteger('minted_count')->default(1);
            $table->decimal('base_price', 12, 2);
            $table->string('palette_from')->default('#7c3aed');
            $table->string('palette_to')->default('#06b6d4');
            $table->timestamps();
        });

        Schema::create('nfts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('token_number');
            $table->string('token_hash')->unique();
            $table->boolean('in_sale')->default(false);
            $table->timestamps();

            $table->unique(['collection_id', 'token_number']);
        });

        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nft_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('nft_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('position')->unique();
            $table->string('previous_hash', 64);
            $table->string('current_hash', 64)->unique();
            $table->string('event');
            $table->timestamp('occurred_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('listings');
        Schema::dropIfExists('nfts');
        Schema::dropIfExists('collections');
    }
};
