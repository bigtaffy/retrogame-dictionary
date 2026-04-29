<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 一次資料源同步的 audit row（誰、何時、跑哪個主機、結果）。
 */
class SyncRun extends Model
{
    protected $fillable = [
        'data_source_id', 'console_id', 'status',
        'started_at', 'finished_at',
        'items_attempted', 'items_succeeded', 'items_failed',
        'summary', 'error_log', 'triggered_by',
    ];

    protected $casts = [
        'summary' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function console(): BelongsTo
    {
        return $this->belongsTo(Console::class);
    }

    public function durationSeconds(): ?int
    {
        if (! $this->finished_at) {
            return null;
        }

        return $this->finished_at->diffInSeconds($this->started_at);
    }
}
