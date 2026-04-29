<?php

use App\Http\Controllers\Api\V1\GameController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

/*
| 公開 API 入口（v3 合約以 Docs/03-API-Endpoints.md 為準）。
| 遊戲 API 的 data[] 內單筆與 v2 `data/games.json` 相同鍵名。
*/
Route::prefix('v1')->group(function (): void {
    Route::get('/health', function (): JsonResponse {
        return response()->json([
            'ok' => true,
            'service' => 'retrogame-dictionary-api',
            'version' => 'v3-dev',
            'time' => now()->toIso8601String(),
        ]);
    });

    Route::get('/games', [GameController::class, 'index']);
    Route::get('/games/{legacy_id}', [GameController::class, 'show'])->where('legacy_id', '[^/]+');
});
