<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 對應 Docs/02-Database-Schema.md §3.7 `game_genres`
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_genres', function (Blueprint $table) {
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->unsignedSmallInteger('genre_id');
            $table->boolean('is_primary')->default(false);
            $table->primary(['game_id', 'genre_id']);

            $table->foreign('genre_id')->references('id')->on('genres')->cascadeOnDelete();
            $table->index(['genre_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_genres');
    }
};
