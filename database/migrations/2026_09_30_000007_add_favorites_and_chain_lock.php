<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['nft' => 'nfts', 'collection' => 'collections'] as $type => $target) {
            Schema::create($type.'_favorites', function (Blueprint $table) use ($type, $target) {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId($type.'_id')->constrained($target)->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['user_id', $type.'_id']);
            });
        }
        Schema::create('chain_locks', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
        });
        DB::table('chain_locks')->insert(['id' => 1]);
        Schema::table('collections', function (Blueprint $table) {
            $table->mediumText('image_data')->nullable();
            $table->string('image_mime')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nft_favorites');
        Schema::dropIfExists('collection_favorites');
        Schema::dropIfExists('chain_locks');
        Schema::table('collections', fn (Blueprint $table) => $table->dropColumn(['image_data', 'image_mime']));
    }
};
