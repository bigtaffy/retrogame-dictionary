<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Title extends Model
{
    protected $fillable = [
        'game_id', 'language', 'text', 'is_aka', 'source',
    ];

    protected function casts(): array
    {
        return [
            'is_aka' => 'boolean',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}
