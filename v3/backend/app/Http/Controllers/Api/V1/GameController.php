<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * 列表：data[] 內單筆與 v2 相同，來自正規化表組裝（Docs/02）
     */
    public function index(Request $request): JsonResponse
    {
        $console = $request->query('console', 'pce');
        $perPage = min(100, max(1, (int) $request->query('per_page', 24)));
        $letter = $request->query('letter');
        $q = $request->query('q');

        $query = Game::forConsoleSlug($console)
            ->orderBy('letter')
            ->orderBy('slug')
            ->with([
                'titles',
                'descriptions',
                'coverImage',
                'images' => fn ($i) => $i->where('kind', 'snap')->orderBy('sort_order'),
                'videos' => fn ($v) => $v->orderBy('sort_order'),
            ]);

        if ($letter !== null && $letter !== '') {
            $query->where('letter', $letter);
        }
        if ($q !== null && $q !== '') {
            $qq = '%'.$q.'%';
            $query->where(function ($w) use ($qq) {
                $w->where('slug', 'like', $qq)
                    ->orWhere('maker', 'like', $qq)
                    ->orWhere('publisher', 'like', $qq)
                    ->orWhere('no_intro_name', 'like', $qq)
                    ->orWhereHas('titles', function ($t) use ($qq) {
                        $t->where('text', 'like', $qq);
                    });
            });
        }

        $page = max(1, (int) $request->query('page', 1));
        $p = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $p->getCollection()->map(fn (Game $g) => $g->toV2Array())->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $legacyId): JsonResponse
    {
        $console = $request->query('console', 'pce');
        $game = Game::forConsoleSlug($console)
            ->where('slug', $legacyId)
            ->with([
                'titles',
                'descriptions',
                'coverImage',
                'images' => fn ($i) => $i->where('kind', 'snap')->orderBy('sort_order'),
                'videos' => fn ($v) => $v->orderBy('sort_order'),
            ])
            ->firstOrFail();

        return response()->json([
            'data' => $game->toV2Array(),
        ]);
    }
}
