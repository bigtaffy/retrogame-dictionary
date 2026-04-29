<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 對應 Docs/02-Database-Schema.md §3.2 對 titles / images 外鍵（games 建完且 titles、images 已存在後再掛）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->foreign('primary_title_id')
                ->references('id')
                ->on('titles')
                ->nullOnDelete();
            $table->foreign('cover_image_id')
                ->references('id')
                ->on('images')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropForeign(['primary_title_id']);
            $table->dropForeign(['cover_image_id']);
        });
    }
};
