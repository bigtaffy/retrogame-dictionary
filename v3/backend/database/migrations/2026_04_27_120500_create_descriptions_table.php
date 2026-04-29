<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 對應 Docs/02-Database-Schema.md §3.4 `descriptions`
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->enum('kind', ['overview', 'comment', 'plot', 'gameplay', 'review']);
            $table->char('language', 3);
            $table->text('text');
            $table->string('source', 64);
            $table->string('source_url', 512)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['game_id', 'kind', 'language', 'is_primary']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE descriptions ADD FULLTEXT KEY ft_desc_text (text) WITH PARSER ngram');
            } catch (\Throwable) {
                try {
                    DB::statement('ALTER TABLE descriptions ADD FULLTEXT KEY ft_desc_text (text)');
                } catch (\Throwable) {
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('descriptions');
    }
};
