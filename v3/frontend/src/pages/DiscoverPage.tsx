import { useCallback, useEffect, useMemo, useState } from 'react'
import { fetchAllConsoleGames } from '../api/gamesApi'
import { isConsoleSlug, type ConsoleSlug } from '../config/consoles'
import { GameCard } from '../components/GameCard'
import type { V2Game } from '../types/v2Game'

function currentConsoleFromLs(): ConsoleSlug {
  try {
    const c = localStorage.getItem('rgd-last-console')
    if (c && isConsoleSlug(c)) {
      return c
    }
  } catch {
    // ignore
  }
  return 'pce'
}

function getViewClass(): 'list' | '' {
  try {
    const v = localStorage.getItem('rgd-view')
    return v === 'list' ? 'list' : ''
  } catch {
    return ''
  }
}

/**
 * 與 `app.html` 的 `renderDiscover()` 相同文案與結構
 */
export function DiscoverPage() {
  const [games, setGames] = useState<V2Game[] | null>(null)
  const [load, setLoad] = useState(true)
  const [consoleName, setConsoleName] = useState<ConsoleSlug>(currentConsoleFromLs())
  const viewList = getViewClass()
  const cardView = viewList ? 'list' : 'grid'

  const run = useCallback(async () => {
    setLoad(true)
    const slug = currentConsoleFromLs()
    setConsoleName(slug)
    try {
      setGames(await fetchAllConsoleGames(slug, 100))
    } catch {
      setGames([])
    } finally {
      setLoad(false)
    }
  }, [])

  useEffect(() => {
    void run()
  }, [run])

  const random = useMemo(
    () => (games ? [...games].sort(() => Math.random() - 0.5).slice(0, 12) : []),
    [games],
  )
  const top = useMemo(
    () =>
      games
        ? games
            .filter((g) => (g.rating || '').toLowerCase().replace(/\s+/g, '').includes('buy'))
            .slice(0, 12)
        : [],
    [games],
  )
  const zhPicks = useMemo(
    () => (games ? games.filter((g) => g.title_zh && g.overview_zh).slice(0, 12) : []),
    [games],
  )

  if (load) {
    return (
      <div className="spinner-wrap">
        <div className="spinner" />
        <div className="spinner-label">Loading discover...</div>
      </div>
    )
  }

  if (!games) {
    return null
  }

  return (
    <>
      <div className="discover-section">
        <h2>{"🎲 Today's Random"}</h2>
        <div className={'grid' + (viewList ? ' list' : '')} id="grid-random">
          {random.map((g) => (
            <GameCard key={g.id} g={g} consoleName={consoleName} view={cardView} />
          ))}
        </div>
      </div>
      <div className="discover-section">
        <h2>🏆 Top Picks (Buy It)</h2>
        <div className={'grid' + (viewList ? ' list' : '')} id="grid-top">
          {top.map((g) => (
            <GameCard key={g.id} g={g} consoleName={consoleName} view={cardView} />
          ))}
        </div>
      </div>
      <div className="discover-section">
        <h2>🇹🇼 中文化精選</h2>
        <div className={'grid' + (viewList ? ' list' : '')} id="grid-zh">
          {zhPicks.map((g) => (
            <GameCard key={g.id} g={g} consoleName={consoleName} view={cardView} />
          ))}
        </div>
      </div>
    </>
  )
}
