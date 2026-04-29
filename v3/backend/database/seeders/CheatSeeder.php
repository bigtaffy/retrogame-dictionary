<?php

namespace Database\Seeders;

use App\Models\Cheat;
use App\Models\Game;
use Illuminate\Database\Seeder;

/**
 * 灌 3 筆 demo 秘技進去當煙霧測試（Stage 1 verification）。
 *   php artisan db:seed --class=CheatSeeder
 */
class CheatSeeder extends Seeder
{
    public function run(): void
    {
        // 隨便挑一筆 game 作目標。
        $game = Game::query()->orderBy('id')->first();
        if (! $game) {
            $this->command?->warn('games 表是空的，跳過 CheatSeeder');
            return;
        }

        $rows = [
            [
                'game_id'        => $game->id,
                'type'           => 'button_sequence',
                'difficulty'     => 'medium',
                'effect_zh'      => '30 條命',
                'effect_en'      => '30 lives',
                'effect_jp'      => '30機',
                'description_zh' => 'Konami code 經典：標題畫面輸入。',
                'code'           => '↑↑↓↓←→←→BA',
                'trigger_at'     => '標題畫面',
                'source'         => 'manual',
                'verified'       => true,
                'sort_order'     => 0,
            ],
            [
                'game_id'        => $game->id,
                'type'           => 'game_genie',
                'difficulty'     => 'easy',
                'effect_zh'      => '無敵',
                'effect_en'      => 'Invincibility',
                'code'           => 'SXIOPO',
                'source'         => 'gamefaqs',
                'verified'       => false,
                'sort_order'     => 1,
            ],
            [
                'game_id'        => $game->id,
                'type'           => 'glitch',
                'difficulty'     => 'hard',
                'effect_zh'      => '穿牆 bug',
                'description_zh' => 'Stage 3 在右下角貼牆按 jump+down，可穿牆跳關。',
                'trigger_at'     => 'Stage 3 右下角',
                'source'         => 'tcrf',
                'verified'       => false,
                'sort_order'     => 2,
            ],
        ];

        foreach ($rows as $row) {
            Cheat::firstOrCreate(
                ['game_id' => $row['game_id'], 'type' => $row['type'], 'effect_zh' => $row['effect_zh']],
                $row,
            );
        }

        $this->command?->info('CheatSeeder: 灌 '.count($rows).' 筆給 game#'.$game->id.' ('.$game->slug.')');
    }
}
