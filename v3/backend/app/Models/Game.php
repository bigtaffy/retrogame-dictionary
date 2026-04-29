<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 對應 `games` 表（Docs/02-Database-Schema.md §3.2）
 */
class Game extends Model
{
    protected $fillable = [
        'console_id', 'slug', 'no_intro_name', 'letter', 'primary_title_id', 'maker', 'publisher',
        'release_year', 'release_date_jp', 'release_date_na', 'release_date_eu', 'format_category',
        'rating', 'external_links', 'source_origin', 'view_count', 'cover_image_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'external_links' => 'array',
            'release_date_jp' => 'date',
            'release_date_na' => 'date',
            'release_date_eu' => 'date',
        ];
    }

    public function console(): BelongsTo
    {
        return $this->belongsTo(Console::class, 'console_id');
    }

    public function primaryTitle(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'primary_title_id');
    }

    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(GameImage::class, 'cover_image_id');
    }

    public function titles(): HasMany
    {
        return $this->hasMany(Title::class, 'game_id');
    }

    public function descriptions(): HasMany
    {
        return $this->hasMany(Description::class, 'game_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(GameImage::class, 'game_id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'game_id');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'game_genres', 'game_id', 'genre_id')
            ->withPivot('is_primary');
    }

    public function scopeForConsoleSlug(Builder $q, string $slug): Builder
    {
        return $q->whereHas('console', fn (Builder $c) => $c->where('slug', $slug));
    }

    /**
     * 還原成 v2 `data/games.json` 單筆物件形狀（供舊站相容）
     *
     * @return array<string, mixed>
     */
    public function toV2Array(): array
    {
        $v2 = is_array($this->external_links) ? ($this->external_links['v2'] ?? []) : [];
        if (! is_array($v2)) {
            $v2 = [];
        }

        $tEn = $this->relationLoaded('titles')
            ? $this->titles->where('language', 'en')->where('is_aka', false)->first()
            : $this->titles()->where('language', 'en')->where('is_aka', false)->first();
        $tJp = $this->relationLoaded('titles')
            ? $this->titles->where('language', 'jp')->where('is_aka', false)->first()
            : $this->titles()->where('language', 'jp')->where('is_aka', false)->first();
        $tZh = $this->relationLoaded('titles')
            ? $this->titles->where('language', 'zh')->where('is_aka', false)->first()
            : $this->titles()->where('language', 'zh')->where('is_aka', false)->first();
        $akaT = $this->relationLoaded('titles')
            ? $this->titles->where('is_aka', true)->first()
            : $this->titles()->where('is_aka', true)->first();

        $ovEn = $this->relationLoaded('descriptions')
            ? $this->descriptions->where('kind', 'overview')->where('language', 'en')->where('is_primary', true)->first()
            : $this->descriptions()->where('kind', 'overview')->where('language', 'en')->where('is_primary', true)->first();
        $ovZh = $this->relationLoaded('descriptions')
            ? $this->descriptions->where('kind', 'overview')->where('language', 'zh')->where('is_primary', true)->first()
            : $this->descriptions()->where('kind', 'overview')->where('language', 'zh')->where('is_primary', true)->first();
        $cmEn = $this->relationLoaded('descriptions')
            ? $this->descriptions->where('kind', 'comment')->where('language', 'en')->where('is_primary', true)->first()
            : $this->descriptions()->where('kind', 'comment')->where('language', 'en')->where('is_primary', true)->first();
        $cmZh = $this->relationLoaded('descriptions')
            ? $this->descriptions->where('kind', 'comment')->where('language', 'zh')->where('is_primary', true)->first()
            : $this->descriptions()->where('kind', 'comment')->where('language', 'zh')->where('is_primary', true)->first();

        $cover = $this->coverImage?->url ?? '';
        $snaps = $this->relationLoaded('images')
            ? $this->images->where('kind', 'snap')->sortBy('sort_order')->pluck('url')->values()->all()
            : $this->images()->where('kind', 'snap')->orderBy('sort_order')->pluck('url')->all();
        $yt = $this->relationLoaded('videos')
            ? $this->videos->sortBy('sort_order')->values()->pluck('external_id')->all()
            : $this->videos()->orderBy('sort_order')->pluck('external_id')->all();

        $ratingOut = array_key_exists('rating_raw', $v2)
            ? (string) $v2['rating_raw']
            : (string) ($this->rating ?? 'unrated');

        $regions = [];
        if (isset($v2['regions']) && is_array($v2['regions'])) {
            $regions = array_values(array_map('strval', $v2['regions']));
        }

        $yearOut = (string) ($v2['year'] ?? '');
        if ($yearOut === '' && $this->release_year !== null) {
            $yearOut = (string) $this->release_year;
        }

        return [
            'id' => $this->slug,
            'letter' => (string) ($this->letter ?? ''),
            'title_en' => (string) ($tEn->text ?? ''),
            'title_jp' => (string) ($tJp->text ?? ''),
            'title_zh' => (string) ($tZh->text ?? ''),
            'aka' => (string) ($v2['aka'] ?? ($akaT->text ?? '')),
            'maker' => (string) ($this->maker ?? ''),
            'publisher' => (string) ($this->publisher ?? ''),
            'developer' => (string) ($v2['developer'] ?? ''),
            'release_date' => (string) ($v2['raw_release_date'] ?? ''),
            'style' => (string) ($v2['style'] ?? ''),
            'format' => (string) ($v2['format'] ?? ''),
            'rating' => $ratingOut,
            'overview_en' => (string) ($ovEn->text ?? ''),
            'overview_zh' => (string) ($ovZh->text ?? ''),
            'comment_en' => (string) ($cmEn->text ?? ''),
            'comment_zh' => (string) ($cmZh->text ?? ''),
            'cover' => (string) $cover,
            'source_url' => (string) ($v2['source_url'] ?? ''),
            'format_category' => (string) ($this->format_category ?? ''),
            'screenshots' => $snaps,
            'genre_category' => (string) ($v2['genre_category'] ?? ''),
            'youtube' => $yt,
            'no_intro_name' => (string) ($this->no_intro_name ?? ''),
            'region_category' => (string) ($v2['region_category'] ?? ''),
            'region_flags' => (string) ($v2['region_flags'] ?? ''),
            'regions' => $regions,
            'year' => $yearOut,
        ];
    }
}
