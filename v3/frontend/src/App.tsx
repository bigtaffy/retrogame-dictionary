import { HashRouter, Navigate, Route, Routes } from 'react-router-dom'
import { LightboxProvider } from './context/LightboxContext'
import { SearchUiProvider } from './context/SearchUiContext'
import { AppLayout } from './layout/AppLayout'
import { DiscoverPage } from './pages/DiscoverPage'
import { GameDetailPage } from './pages/GameDetailPage'
import { LibraryPage } from './pages/LibraryPage'
import { SettingsPage } from './pages/SettingsPage'

/**
 * 與 v2 `app.html` 相同之 hash 路由（見其 Route formats 註解）
 */
export default function App() {
  return (
    <SearchUiProvider>
      <LightboxProvider>
        <HashRouter>
          <Routes>
            <Route element={<AppLayout />}>
              <Route path="/" element={<Navigate to="/pce" replace />} />
              <Route path="/discover" element={<DiscoverPage />} />
              <Route path="/settings" element={<SettingsPage />} />
              <Route path="/favorites" element={<Navigate to="/pce" replace />} />
              <Route path="/:console/g/:id" element={<GameDetailPage />} />
              <Route path="/:console" element={<LibraryPage />} />
            </Route>
          </Routes>
        </HashRouter>
      </LightboxProvider>
    </SearchUiProvider>
  )
}
