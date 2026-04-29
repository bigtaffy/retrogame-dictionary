<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 對應 Docs/02-Database-Schema.md §3.8 `game_regions`
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_regions', function (Blueprint $table) {
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->unsignedTinyInteger('region_id');
            $table->date('release_date')->nullable();
            $table->string('product_code', 64)->nullable();
            $table->primary(['game_id', 'region_id']);

            $table->foreign('region_id')->references('id')->on('regions')->cascadeOnDelete();
            $table->index('region_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_regions');
    }
};
