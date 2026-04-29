import { useEffect } from 'react'
import { NavLink, Outlet, useLocation } from 'react-router-dom'
import { CONSOLE_CONFIG, CONSOLE_ORDER, type ConsoleSlug } from '../config/consoles'
import { SearchOverlay } from '../components/SearchOverlay'
import { useSearchUi } from '../context/SearchUiContext'
import { useTextSizeInit, useCycleTextSize } from '../hooks/useTextSize'
import { isLibraryTabActive, useLibraryTo } from '../hooks/useLibraryPath'

/**
 * 與 v2 `app.html` 相同：.topbar + .console-picker、桌面分頁、字級、搜尋鈕、main、搜尋層、底欄
 */
export function AppLayout() {
  useTextSizeInit()
  const { pathname } = useLocation()
  const { setOverlayOpen } = useSearchUi()
  const cycleText = useCycleTextSize()
  const libTo = useLibraryTo()
  const libOn = isLibraryTabActive(pathname)
  const discoverOn = pathname.split('/').includes('discover')
  const settingsOn = pathname.split('/').includes('settings')

  useEffect(() => {
    const on = () => {
      document.body.classList.toggle('scrolled', window.scrollY > 24)
    }
    on()
    window.addEventListener('scroll', on, { passive: true })
    return () => window.removeEventListener('scroll', on)
  }, [])

  return (
    <>
      <div className="app">
        <header className="topbar" id="topbar">
          <div className="topbar-row">
            <div className="console-picker" id="console-picker">
              {CONSOLE_ORDER.map((slug) => (
                <NavLink
                  key={slug}
                  to={`/${slug}`}
                  className={({ isActive }) => 'console-pill' + (isActive ? ' active' : '')}
                >
                  <img
                    className="ico-img"
                    src={`/icons/${slug === 'pce' ? 'pce' : slug === 'md' ? 'md' : slug}.png`}
                    alt={CONSOLE_CONFIG[slug as ConsoleSlug].name}
                    draggable={false}
                  />
                </NavLink>
              ))}
            </div>
            <div className="topbar-right">
              <div className="tabs-desktop" id="tabs-desktop">
                <NavLink
                  to={libTo}
                  className={libOn ? 'active' : undefined}
                >
                  <span>📚</span> Library
                </NavLink>
                <NavLink
                  to="/discover"
                  className={discoverOn ? 'active' : ''}
                >
                  <span>🎲</span> Discover
                </NavLink>
                <NavLink
                  to="/settings"
                  className={settingsOn ? 'active' : ''}
                >
                  <span>⚙</span> Settings
                </NavLink>
              </div>
              <button
                type="button"
                className="text-size-btn"
                title="字級"
                aria-label="調整字級"
                onClick={cycleText}
              >
                <svg width="22" height="16" viewBox="0 0 22 16" xmlns="http://www.w3.org/2000/svg" aria-hidden>
                  <text x="1" y="14" fill="currentColor" fontSize="11" fontWeight="700" fontFamily="-apple-system, system-ui, sans-serif">A</text>
                  <text x="9" y="14" fill="currentColor" fontSize="15" fontWeight="800" fontFamily="-apple-system, system-ui, sans-serif">A</text>
                </svg>
              </button>
              <button
                type="button"
                className="search-icon-btn"
                title="Search"
                aria-label="Search"
                onClick={() => setOverlayOpen(true)}
              >
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden>
                  <circle cx="7" cy="7" r="5" stroke="currentColor" strokeWidth="1.8" />
                  <path d="M11 11L14.5 14.5" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
                </svg>
              </button>
            </div>
          </div>
        </header>
        <main id="main">
          <div id="view-container">
            <Outlet />
          </div>
        </main>
        <SearchOverlay />
      </div>
      <nav className="tabbar" id="tabbar" aria-label="分頁">
        <NavLink to={libTo} className={libOn ? 'active' : ''}>
          <span className="ico">📚</span>
          <span>LIBRARY</span>
        </NavLink>
        <NavLink to="/discover" className={discoverOn ? 'active' : ''}>
          <span className="ico">🎲</span>
          <span>DISCOVER</span>
        </NavLink>
        <NavLink to="/settings" className={settingsOn ? 'active' : ''}>
          <span className="ico">⚙</span>
          <span>SETTINGS</span>
        </NavLink>
      </nav>
    </>
  )
}
