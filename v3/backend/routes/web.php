<?php

use App\Http\Controllers\V2DataFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// v3 前端（Vite）代理讀取與 v2 同結構的 data/*.json
Route::get('/v2data/{path}', V2DataFileController::class)->where('path', '.*');
