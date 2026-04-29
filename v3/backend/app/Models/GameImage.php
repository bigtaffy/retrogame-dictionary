<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 對應表名 `images`（避免與其他套件 Image 類別衝突，模型名 GameImage）
 */
class GameImage extends Model
{
    protected $table = 'images';

    protected $fillable = [
        'game_id', 'kind', 'url', 'thumb_url', 'width', 'height', 'source', 'region', 'sort_order',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}
