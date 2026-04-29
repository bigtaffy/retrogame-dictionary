<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 對應 Docs/02-Database-Schema.md §3.5 `images`
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->enum('kind', ['cover', 'title_screen', 'snap', 'marquee', 'wheel', 'box_back', 'cart', 'manual']);
            $table->string('url', 1024);
            $table->string('thumb_url', 1024)->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('source', 64)->nullable();
            $table->char('region', 3)->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['game_id', 'kind', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
