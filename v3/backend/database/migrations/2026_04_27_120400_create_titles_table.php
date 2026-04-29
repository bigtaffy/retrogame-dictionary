<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 對應 Docs/02-Database-Schema.md §3.3 `titles`
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->char('language', 3);
            $table->string('text', 255);
            $table->boolean('is_aka')->default(false);
            $table->string('source', 32)->nullable();
            $table->timestamps();

            $table->index(['game_id', 'language']);
        });

        if ($this->driver() === 'mysql') {
            try {
                DB::statement('ALTER TABLE titles ADD FULLTEXT KEY ft_titles_text (text) WITH PARSER ngram');
            } catch (\Throwable) {
                try {
                    DB::statement('ALTER TABLE titles ADD FULLTEXT KEY ft_titles_text (text)');
                } catch (\Throwable) {
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('titles');
    }

    private function driver(): string
    {
        return Schema::getConnection()->getDriverName();
    }
};
