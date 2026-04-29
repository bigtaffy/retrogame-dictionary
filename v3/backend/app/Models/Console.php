<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 對應 `consoles` 表（Docs/02-Database-Schema.md §3.1）
 */
class Console extends Model
{
    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id', 'slug', 'name_en', 'name_zh', 'name_jp', 'manufacturer', 'release_year',
        'icon_url', 'sort_order', 'game_count_cached',
    ];

    public function games(): HasMany
    {
        return $this->hasMany(Game::class, 'console_id');
    }
}
