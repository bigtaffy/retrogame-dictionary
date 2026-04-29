<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 把 consoles.name_zh 從早期亂寫的「掌機 / 彩色掌機 / 掌機進化 / Mega Drive」
 * 修成台灣玩家熟的中文叫法。
 *
 * 為什麼：原本的命名沒有語境（"掌機" 太空泛、"Mega Drive" 跟 name_en 重複），
 * 後台 GamesTable 主機 column 用的是 name_zh，第一眼會看不出哪個主機。
 */
return new class extends Migration
{
    private array $names = [
        'pce' => 'PC Engine',
        'gb'  => 'Game Boy',
        'gbc' => 'Game Boy Color',
        'gba' => 'Game Boy Advance',
        'fc'  => '紅白機 FC',
        'md'  => '世嘉 MD',
    ];

    public function up(): void
    {
        foreach ($this->names as $slug => $zh) {
            DB::table('consoles')->where('slug', $slug)->update([
                'name_zh' => $zh,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // 還原成原本初始 seed 那組（精確值已不可考，用 name_en fallback）
        DB::table('consoles')->update([
            'name_zh' => DB::raw('name_en'),
        ]);
    }
};
