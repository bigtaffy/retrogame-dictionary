<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 前端 CONSOLE_CONFIG 含 md（Mega Drive），與 data/md/games.json 對應
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('consoles')->insert([
            'id' => 6,
            'slug' => 'md',
            'name_en' => 'Mega Drive',
            'name_zh' => 'Mega Drive',
            'name_jp' => 'メガドライブ',
            'manufacturer' => 'Sega',
            'release_year' => 1988,
            'icon_url' => null,
            'sort_order' => 60,
            'game_count_cached' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('consoles')->where('id', 6)->delete();
    }
};
