<?php

namespace App\Policies;

use App\Models\Console;
use App\Models\User;

/**
 * 主機為系統內建字典：不可新增、不可刪除，可檢視與小修改（名稱、排序、圖示等）
 */
class ConsolePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Console $console): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Console $console): bool
    {
        return true;
    }

    public function delete(User $user, Console $console): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
