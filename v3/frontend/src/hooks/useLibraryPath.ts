import { useLocation } from 'react-router-dom'
import { isConsoleSlug } from '../config/consoles'

export function useLibraryTo(): string {
  const { pathname } = useLocation()
  const s = pathname.split('/').filter(Boolean)
  if (s[0] && isConsoleSlug(s[0])) {
    if (typeof localStorage !== 'undefined') {
      try {
        localStorage.setItem('rgd-last-console', s[0])
      } catch {
        // ignore
      }
    }
    return `/${s[0]}`
  }
  if (typeof localStorage !== 'undefined') {
    try {
      const c = localStorage.getItem('rgd-last-console')
      if (c && isConsoleSlug(c)) {
        return `/${c}`
      }
    } catch {
      // ignore
    }
  }
  return '/pce'
}

export function isLibraryTabActive(pathname: string): boolean {
  const s = pathname.split('/').filter(Boolean)
  if (s[0] && isConsoleSlug(s[0])) {
    return true
  }
  return false
}
