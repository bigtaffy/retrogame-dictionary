<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * 本機開發時，讓前端可透過同源 proxy 讀取倉庫根目錄 `data/*.json`（與 v2 靜態站相同路徑）。
 * 只允許白名單內的相對路徑。
 */
class V2DataFileController extends Controller
{
    /** @var list<string> */
    private const ALLOWED = [
        'games.json',
        'gba/games.json',
        'fc/games.json',
        'gb/games.json',
        'gbc/games.json',
        'md/games.json',
    ];

    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        if (! in_array($path, self::ALLOWED, true)) {
            abort(404);
        }

        $root = dirname(dirname(base_path()));
        $full = $root.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
        $real = realpath($full);
        $dataRoot = realpath($root.DIRECTORY_SEPARATOR.'data');

        if ($real === false || $dataRoot === false || ! str_starts_with($real, $dataRoot)) {
            abort(404);
        }

        if (! is_file($real) || ! is_readable($real)) {
            abort(404);
        }

        return response()->file($real, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }
}
