<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 對應 `data_sources`：登錄外部 API（libretro / TGDB / SS / Wiki）狀態。
 */
class DataSource extends Model
{
    protected $fillable = [
        'slug', 'name', 'kind', 'status', 'endpoint', 'config', 'coverage',
        'last_synced_at', 'last_failed_at', 'last_error',
        'success_count', 'fail_count', 'enabled',
    ];

    protected $casts = [
        'config' => 'array',
        'coverage' => 'array',
        'last_synced_at' => 'datetime',
        'last_failed_at' => 'datetime',
        'enabled' => 'boolean',
    ];

    public function syncRuns(): HasMany
    {
        return $this->hasMany(SyncRun::class);
    }

    /** 用於 admin UI 的小燈號 emoji */
    protected function statusBadge(): Attribute
    {
        return Attribute::get(fn () => match ($this->status) {
            'ok'       => '🟢 正常',
            'warning'  => '🟡 警告',
            'error'    => '🔴 異常',
            'disabled' => '⚫ 停用',
            default    => '⚪ 未知',
        });
    }

    /** 過去 24 小時內是否有跑過 */
    public function isStale(): bool
    {
        return ! $this->last_synced_at || $this->last_synced_at->lt(now()->subDay());
    }
}
