<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Genre extends Model
{
    public $timestamps = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'slug', 'name_en', 'name_zh', 'parent_id', 'sort_order',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Genre::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Genre::class, 'parent_id');
    }

    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_genres', 'genre_id', 'game_id')
            ->withPivot('is_primary');
    }
}
