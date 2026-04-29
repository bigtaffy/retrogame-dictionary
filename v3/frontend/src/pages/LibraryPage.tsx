import { useCallback, useEffect, useMemo, useState } from 'react'
import { Navigate, useNavigate, useParams } from 'react-router-dom'
import { fetchAllConsoleGames } from '../api/gamesApi'
import { CONSOLE_CONFIG, isConsoleSlug } from '../config/consoles'
import { useSearchUi } from '../context/SearchUiContext'
import { GameCard } from '../components/GameCard'
import { ratingBadge } from '../lib/v2GameUi'
import type { V2Game } from '../types/v2Game'

const CAP = 600

type ViewMode = 'grid' | 'list' | 'covers'
type RankFilter = 'all' | 'buyit' | 'tryit' | 'avoid'

function getViewFromLs(): ViewMode {
  try {
    const v = localStorage.getItem('rgd-view') as ViewMode
    if (v === 'grid' || v === 'list' || v === 'covers') {
      return v
    }
  } catch {
    // ignore
  }
  return 'grid'
}

function applyV2Filters(
  games: V2Game[],
  f: {
    search: string
    format: string
    genre: string
    region: string
    rating: RankFilter
    letter: string
  },
): V2Game[] {
  let out = games
  if (f.search) {
    const q = f.search.toLowerCase()
    out = out.filter(
      (g) =>
        (g.title_en || '').toLowerCase().includes(q) ||
        (g.title_zh || '').toLowerCase().includes(q) ||
        (g.title_jp || '').toLowerCase().includes(q) ||
        (g.maker || '').toLowerCase().includes(q) ||
        (g.publisher || '').toLowerCase().includes(q) ||
        (g.aka || '').toLowerCase().includes(q),
    )
  }
  if (f.format) {
    out = out.filter((g) => (g.format_category || g.format) === f.format)
  }
  if (f.genre) {
    out = out.filter((g) => g.genre_category === f.genre)
  }
  if (f.region) {
    out = out.filter((g) => g.region_category === f.region)
  }
  if (f.rating !== 'all') {
    out = out.filter((g) => {
      const rb = ratingBadge(g.rating)
      if (!rb) {
        return false
      }
      return rb.cls === 'rating-' + f.rating
    })
  }
  if (f.letter) {
    out = out.filter(
      (g) =>
        (String(g.letter || (g.title_en || '?')[0] || '?').toUpperCase() as string) ===
        f.letter,
    )
  }
  return out
}

export function LibraryPage() {
  const { console: raw } = useParams()
  const navigate = useNavigate()
  const consoleSlug = raw && isConsoleSlug(raw) ? raw : null
  const { searchQuery } = useSearchUi()
  const cfg = consoleSlug ? CONSOLE_CONFIG[consoleSlug] : null

  const [rawGames, setRawGames] = useState<V2Game[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [loadFailed, setLoadFailed] = useState(false)
  const [view, setView] = useState<ViewMode>(getViewFromLs)

  const [format, setFormat] = useState('')
  const [genre, setGenre] = useState('')
  const [region, setRegion] = useState('')
  const [rating, setRating] = useState<RankFilter>('all')
  const [letter, setLetter] = useState('')

  const loadGames = useCallback(async () => {
    if (!consoleSlug) {
      return
    }
    setIsLoading(true)
    setLoadFailed(false)
    try {
      setRawGames(await fetchAllConsoleGames(consoleSlug, 100))
    } catch {
      setLoadFailed(true)
      setRawGames([])
    } finally {
      setIsLoading(false)
    }
  }, [consoleSlug])

  useEffect(() => {
    void loadGames()
  }, [loadGames])

  const filtered = useMemo(() => {
    return applyV2Filters(rawGames, {
      search: searchQuery,
      format,
      genre,
      region,
      rating,
      letter,
    })
  }, [rawGames, searchQuery, format, genre, region, rating, letter])

  const formatOpts = useMemo(
    () =>
      [
        ...new Set(
          rawGames.map((g) => g.format_category || g.format).filter(Boolean),
        ),
      ] as string[],
    [rawGames],
  )
  const genreOpts = useMemo(
    () => [...new Set(rawGames.map((g) => g.genre_category).filter(Boolean))] as string[],
    [rawGames],
  )
  const regionOpts = useMemo(
    () => [...new Set(rawGames.map((g) => g.region_category).filter(Boolean))] as string[],
    [rawGames],
  )

  const letters = useMemo(() => {
    const s = new Set(
      rawGames.map((g) => String((g.letter || (g.title_en || '?')[0] || '?').toUpperCase())),
    )
    return s
  }, [rawGames])

  const setViewMode = (v: ViewMode) => {
    setView(v)
    try {
      localStorage.setItem('rgd-view', v)
    } catch {
      // ignore
    }
  }

  const randomGame = useCallback(() => {
    if (!consoleSlug || !filtered.length) {
      return
    }
    const g = filtered[Math.floor(Math.random() * filtered.length)]
    if (g) {
      navigate(`/${consoleSlug}/g/${encodeURIComponent(g.id)}`)
    }
  }, [consoleSlug, filtered, navigate])

  if (!consoleSlug || !isConsoleSlug(consoleSlug) || !cfg) {
    return <Navigate to="/pce" replace />
  }

  if (isLoading) {
    return (
      <div className="spinner-wrap">
        <div className="spinner" />
        <div className="spinner-label">載入 {cfg.name}...</div>
      </div>
    )
  }

  if (loadFailed) {
    return (
      <div className="empty">
        <h3>載入失敗</h3>
        <p>{`無法載入 ${cfg.name} 資料。請用 http server 開啟（python3 -m http.server）或使用 bundled 版本。`}</p>
      </div>
    )
  }

  const show = filtered.slice(0, CAP)
  const gridClass =
    'grid' + (view === 'list' ? ' list' : view === 'covers' ? ' covers' : '')

  return (
    <div id="view-container-v3">
      <div className="controls-wrap">
        <div className="controls" id="controls-bar">
          {cfg.hasFormat ? (
            <select
              value={format}
              onChange={(e) => setFormat(e.target.value)}
              id="format-filter"
            >
              <option value="">所有規格</option>
              {formatOpts.sort().map((f) => (
                <option key={f} value={f}>
                  {f}
                </option>
              ))}
            </select>
          ) : null}
          <select value={genre} onChange={(e) => setGenre(e.target.value)} id="genre-filter">
            <option value="">所有類型</option>
            {genreOpts.sort().map((f) => (
              <option key={f} value={f}>
                {f}
              </option>
            ))}
          </select>
          {cfg.hasRegion ? (
            <select
              value={region}
              onChange={(e) => setRegion(e.target.value)}
              id="region-filter"
            >
              <option value="">所有地區</option>
              {regionOpts.sort().map((f) => (
                <option key={f} value={f}>
                  {f}
                </option>
              ))}
            </select>
          ) : null}
          <button type="button" className="pill-btn" title="隨機選一款" onClick={randomGame}>
            🎲
          </button>
          <div className="view-toggle">
            <button
              type="button"
              className={view === 'grid' ? 'active' : ''}
              title="大圖"
              onClick={() => setViewMode('grid')}
            >
              <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden>
                <rect x="2" y="2" width="10" height="10" stroke="currentColor" strokeWidth="1.6" />
              </svg>
            </button>
            <button
              type="button"
              className={view === 'covers' ? 'active' : ''}
              title="只看封面"
              onClick={() => setViewMode('covers')}
            >
              <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden>
                <rect x="1" y="1" width="5" height="12" stroke="currentColor" strokeWidth="1.4" />
                <rect x="8" y="1" width="5" height="12" stroke="currentColor" strokeWidth="1.4" />
              </svg>
            </button>
            <button
              type="button"
              className={view === 'list' ? 'active' : ''}
              title="列表"
              onClick={() => setViewMode('list')}
            >
              <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden>
                <rect x="1" y="2" width="12" height="4" stroke="currentColor" strokeWidth="1.4" />
                <rect x="1" y="8" width="12" height="4" stroke="currentColor" strokeWidth="1.4" />
              </svg>
            </button>
          </div>
        </div>
        <div className="rank-bar" id="rank-bar">
          {(
            [
              ['all', '全部'],
              ['buyit', '🏆 必買'],
              ['tryit', '👍 值得試'],
              ['avoid', '⚠ 勸退'],
            ] as [RankFilter, string][]
          ).map(([k, label]) => (
            <button
              type="button"
              key={k}
              className={rating === k ? 'active' : ''}
              onClick={() => setRating(k)}
            >
              {label}
            </button>
          ))}
        </div>
        <div className="az-bar" id="az-bar">
          <button
            type="button"
            className={'all' + (letter === '' ? ' active' : '')}
            onClick={() => setLetter('')}
          >
            ALL
          </button>
          {'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('').map((L) => {
            const has = letters.has(L)
            return (
              <button
                type="button"
                key={L}
                disabled={!has}
                className={letter === L ? 'active' : ''}
                onClick={() => setLetter(L)}
              >
                {L}
              </button>
            )
          })}
        </div>
        <div className="stats" id="stats">
          {filtered.length} / {rawGames.length} GAMES
        </div>
      </div>
      <div className={gridClass} id="grid">
        {show.length === 0 ? (
          <div className="empty" style={{ gridColumn: '1 / -1' }}>
            <h3>No matches</h3>
            <p>調整搜尋或篩選條件試試看</p>
          </div>
        ) : (
          show.map((g) => (
            <GameCard key={g.id} g={g} consoleName={consoleSlug} view={view} />
          ))
        )}
        {filtered.length > CAP ? (
          <div className="empty" style={{ gridColumn: '1 / -1' }}>
            <p>
              顯示前 {CAP} 筆，共 {filtered.length} 筆。再篩細一點吧。
            </p>
          </div>
        ) : null}
      </div>
    </div>
  )
}
