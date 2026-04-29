<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 對應 Docs/02-Database-Schema.md §3.2 `games`（先不加 primary_title / cover 外鍵，於後續 migration 補上）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('console_id');
            $table->string('slug', 120);
            $table->string('no_intro_name', 255)->nullable();
            $table->string('letter', 4)->nullable();
            $table->unsignedBigInteger('primary_title_id')->nullable();
            $table->string('maker', 128)->nullable();
            $table->string('publisher', 128)->nullable();
            $table->unsignedSmallInteger('release_year')->nullable();
            $table->date('release_date_jp')->nullable();
            $table->date('release_date_na')->nullable();
            $table->date('release_date_eu')->nullable();
            $table->string('format_category', 32)->nullable();
            $table->enum('rating', ['buyit', 'tryit', 'avoid', 'unrated'])->default('unrated');
            $table->json('external_links')->nullable();
            $table->string('source_origin', 64)->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedBigInteger('cover_image_id')->nullable();
            $table->timestamps();

            $table->foreign('console_id')->references('id')->on('consoles');
            $table->unique(['console_id', 'slug']);
            $table->index(['console_id', 'letter']);
            $table->index('format_category');
            $table->index('release_year');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
