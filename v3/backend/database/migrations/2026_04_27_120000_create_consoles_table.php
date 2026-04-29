<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 對應 Docs/02-Database-Schema.md §3.1 `consoles`
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consoles', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('slug', 8);
            $table->string('name_en', 64);
            $table->string('name_zh', 64);
            $table->string('name_jp', 64);
            $table->string('manufacturer', 64)->nullable();
            $table->unsignedSmallInteger('release_year')->nullable();
            $table->string('icon_url', 255)->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->unsignedInteger('game_count_cached')->default(0);
            $table->timestamps();
        });

        DB::table('consoles')->insert([
            [
                'id' => 1, 'slug' => 'pce', 'name_en' => 'PC Engine', 'name_zh' => 'PC Engine', 'name_jp' => 'PCエンジン',
                'manufacturer' => 'NEC', 'release_year' => 1987, 'icon_url' => null, 'sort_order' => 10, 'game_count_cached' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => 2, 'slug' => 'gb', 'name_en' => 'Game Boy', 'name_zh' => '掌機', 'name_jp' => 'ゲームボーイ',
                'manufacturer' => 'Nintendo', 'release_year' => 1989, 'icon_url' => null, 'sort_order' => 20, 'game_count_cached' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => 3, 'slug' => 'gbc', 'name_en' => 'Game Boy Color', 'name_zh' => '彩色掌機', 'name_jp' => 'ゲームボーイカラー',
                'manufacturer' => 'Nintendo', 'release_year' => 1998, 'icon_url' => null, 'sort_order' => 30, 'game_count_cached' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => 4, 'slug' => 'gba', 'name_en' => 'Game Boy Advance', 'name_zh' => '掌機進化', 'name_jp' => 'ゲームボーイアドバンス',
                'manufacturer' => 'Nintendo', 'release_year' => 2001, 'icon_url' => null, 'sort_order' => 40, 'game_count_cached' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => 5, 'slug' => 'fc', 'name_en' => 'Famicom', 'name_zh' => '紅白機', 'name_jp' => 'ファミコン',
                'manufacturer' => 'Nintendo', 'release_year' => 1983, 'icon_url' => null, 'sort_order' => 50, 'game_count_cached' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('consoles');
    }
};
