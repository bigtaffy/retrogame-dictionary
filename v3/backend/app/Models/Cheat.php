<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 對應 cheats 表（遊戲秘技 / 密碼 / Game Genie / glitch 等）。
 */
class Cheat extends Model
{
    protected $fillable = [
        'game_id',
        'type',
        'effect_zh', 'effect_en', 'effect_jp',
        'code', 'code_normalized',
        'trigger_at', 'rom_version', 'region',
        'description_zh', 'description_en',
        'difficulty',
        'source', 'source_url', 'contributor_id',
        'verified', 'verified_by', 'verified_at',
        'sort_order',
    ];

    protected $casts = [
        'verified'    => 'boolean',
        'verified_at' => 'datetime',
        'sort_order'  => 'integer',
    ];

    public const TYPES = [
        'button_sequence'    => '按鈕組合',
        'password'           => '密碼',
        'game_genie'         => 'Game Genie',
        'pro_action_replay'  => 'PAR / GameShark',
        'memory_patch'       => '記憶體 patch',
        'glitch'             => 'Glitch / Bug',
        'easter_egg'         => '彩蛋',
        'unlock'             => '解鎖條件',
        'misc'               => '其他',
    ];

    public const DIFFICULTIES = [
        'easy'          => '簡單',
        'medium'        => '中等',
        'hard'          => '困難',
        'speedrun_only' => '速通專用',
        'tas_only'      => 'TAS 專用',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function contributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contributor_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeVerified(Builder $q): Builder
    {
        return $q->where('verified', true);
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('verified', false);
    }

    /**
     * Mutator: 寫 code 時自動產生 code_normalized 給去重 / 搜尋用。
     * 把方向鍵符號統一、大小寫統一、空白移除。
     */
    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['code'] = $value;
        $this->attributes['code_normalized'] = $value !== null ? self::normalizeCode($value) : null;
    }

    public static function normalizeCode(string $code): string
    {
        $map = [
            '↑' => 'U', '←' => 'L', '↓' => 'D', '→' => 'R',
            '上' => 'U', '左' => 'L', '下' => 'D', '右' => 'R',
            '⬆' => 'U', '⬅' => 'L', '⬇' => 'D', '➡' => 'R',
            ' ' => '', "\t" => '', "\n" => '', '+' => '', ',' => '', '、' => '',
        ];
        return strtoupper(strtr($code, $map));
    }
}
