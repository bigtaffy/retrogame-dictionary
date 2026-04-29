import { useCallback, useState } from 'react'
import { clearV2JsonCache } from '../api/v2StaticGames'

/**
 * 與 `app.html` 的 `renderSettings()` 相同文案與結構
 */
export function SettingsPage() {
  const [view, setView] = useState(() => {
    try {
      const v = localStorage.getItem('rgd-view')
      if (v === 'grid' || v === 'list') {
        return v
      }
    } catch {
      // ignore
    }
    return 'grid'
  })

  const setViewMode = (v: 'grid' | 'list') => {
    setView(v)
    try {
      localStorage.setItem('rgd-view', v)
    } catch {
      // ignore
    }
  }

  const clearCache = useCallback(() => {
    clearV2JsonCache()
    alert('已清除快取，下次切換主機會重新載入。')
  }, [])

  return (
    <div className="settings-page">
      <h2>⚙ Settings</h2>

      <div className="settings-row">
        <div className="settings-label">
          Default View 預設顯示
          <small>網格或列表</small>
        </div>
        <div className="settings-control" id="set-view">
          <button
            type="button"
            data-view="grid"
            className={view === 'grid' ? 'active' : ''}
            onClick={() => setViewMode('grid')}
          >
            Grid
          </button>
          <button
            type="button"
            data-view="list"
            className={view === 'list' ? 'active' : ''}
            onClick={() => setViewMode('list')}
          >
            List
          </button>
        </div>
      </div>

      <div className="settings-row">
        <div className="settings-label">
          Cache 資料快取
          <small>清除已載入的遊戲資料（重新載入時重抓）</small>
        </div>
        <div className="settings-control">
          <button type="button" className="danger" id="clear-cache" onClick={clearCache}>
            Clear Cache
          </button>
        </div>
      </div>

      <div className="settings-row">
        <div className="settings-label">
          About
          <small>Retro Game Dictionary v2.0 — PCE / GBA / FC</small>
        </div>
      </div>
    </div>
  )
}
