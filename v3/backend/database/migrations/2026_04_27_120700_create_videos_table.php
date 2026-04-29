<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 對應 Docs/02-Database-Schema.md §3.6 `videos`（Laravel 慣例補上 timestamps 以利同步）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->string('provider', 16)->default('youtube');
            $table->string('external_id', 64);
            $table->string('title', 255)->nullable();
            $table->string('thumb_url', 512)->nullable();
            $table->unsignedInteger('duration_sec')->nullable();
            $table->string('source', 32)->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['game_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
