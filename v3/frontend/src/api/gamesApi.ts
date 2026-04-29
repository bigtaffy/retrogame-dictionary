import type { GameDetailResponse, GamesListResponse, V2Game } from '../types/v2Game'

const base = (import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8001').replace(
  /\/$/,
  '',
)

export async function fetchGamesList(params: {
  page: number
  perPage: number
  q?: string
  letter?: string
  /** 預設 pce，與 v2 主機 slug 相同 */
  console?: string
}): Promise<GamesListResponse> {
  const u = new URL(`${base}/api/v1/games`)
  u.searchParams.set('console', params.console ?? 'pce')
  u.searchParams.set('page', String(params.page))
  u.searchParams.set('per_page', String(params.perPage))
  if (params.q?.trim()) u.searchParams.set('q', params.q.trim())
  if (params.letter) u.searchParams.set('letter', params.letter)

  const r = await fetch(u.toString(), { headers: { Accept: 'application/json' } })
  if (!r.ok) throw new Error(`列表載入失敗：HTTP ${r.status}`)
  return r.json() as Promise<GamesListResponse>
}

/**
 * 拉滿某主機全部列表，供與 v2 相同之篩選（分頁合併；所有主機皆走 API）
 */
export async function fetchAllConsoleGames(consoleSlug: string, perPage = 100): Promise<V2Game[]> {
  const all: V2Game[] = []
  let page = 1
  for (;;) {
    const res = await fetchGamesList({ page, perPage, console: consoleSlug })
    all.push(...res.data)
    if (page >= res.meta.last_page) {
      break
    }
    page += 1
  }
  return all
}

/** @deprecated 使用 fetchAllConsoleGames('pce', …) */
export async function fetchAllPceGames(perPage = 100): Promise<V2Game[]> {
  return fetchAllConsoleGames('pce', perPage)
}

export async function fetchGame(legacyId: string, consoleSlug = 'pce'): Promise<GameDetailResponse> {
  const u = new URL(
    `${base}/api/v1/games/${encodeURIComponent(legacyId)}`,
  )
  u.searchParams.set('console', consoleSlug)
  const r = await fetch(u.toString(), { headers: { Accept: 'application/json' } })
  if (!r.ok) {
    const err = new Error(`HTTP ${r.status}`) as Error & { status: number }
    err.status = r.status
    throw err
  }
  return r.json() as Promise<GameDetailResponse>
}
