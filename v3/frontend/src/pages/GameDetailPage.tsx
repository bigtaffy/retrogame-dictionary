import { useEffect, useState } from 'react'
import { Navigate, useParams } from 'react-router-dom'
import { fetchGame } from '../api/gamesApi'
import { CONSOLE_CONFIG, isConsoleSlug, type ConsoleSlug } from '../config/consoles'
import { GameDetailV2View } from '../components/GameDetailV2View'
import type { V2Game } from '../types/v2Game'

export function GameDetailPage() {
  const { console: raw, id: idParam } = useParams()
  const id = idParam ? decodeURIComponent(idParam) : ''
  const consoleSlug = raw && isConsoleSlug(raw) ? raw : null
  const [game, setGame] = useState<V2Game | null>(null)
  const [loadFailed, setLoadFailed] = useState(false)
  const [load, setLoad] = useState(true)

  useEffect(() => {
    if (!consoleSlug || !id) {
      setLoad(false)
      return
    }
    let cancelled = false
    const run = async () => {
      setLoad(true)
      setLoadFailed(false)
      setGame(null)
      try {
        const res = await fetchGame(id, consoleSlug)
        if (!cancelled) {
          setGame(res.data)
        }
      } catch (e) {
        if (!cancelled) {
          const status =
            typeof e === 'object' && e !== null && 'status' in e
              ? (e as { status: number }).status
              : 0
          if (status === 404) {
            setGame(null)
            setLoadFailed(false)
          } else {
            setLoadFailed(true)
            setGame(null)
          }
        }
      } finally {
        if (!cancelled) {
          setLoad(false)
        }
      }
    }
    void run()
    return () => {
      cancelled = true
    }
  }, [consoleSlug, id])

  if (!consoleSlug || !isConsoleSlug(consoleSlug)) {
    return <Navigate to="/pce" replace />
  }
  if (!id) {
    return <Navigate to={`/${consoleSlug}`} replace />
  }

  if (load) {
    return (
      <div className="spinner-wrap">
        <div className="spinner" />
        <div className="spinner-label">載入中...</div>
      </div>
    )
  }

  if (loadFailed) {
    const name = CONSOLE_CONFIG[consoleSlug as ConsoleSlug].name
    return (
      <div className="empty">
        <h3>載入失敗</h3>
        <p>{`無法載入 ${name} 資料。請用 http server 開啟（python3 -m http.server）或使用 bundled 版本。`}</p>
      </div>
    )
  }

  if (!game) {
    return (
      <div className="empty">
        <h3>Not Found</h3>
        <p>{`找不到這款遊戲：${id}`}</p>
      </div>
    )
  }

  return <GameDetailV2View g={game} consoleName={consoleSlug as ConsoleSlug} />
}
