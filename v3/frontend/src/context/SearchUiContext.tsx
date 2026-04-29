import { createContext, useContext, useMemo, useState, type ReactNode } from 'react'

type Ctx = {
  searchQuery: string
  setSearchQuery: (q: string) => void
  overlayOpen: boolean
  setOverlayOpen: (v: boolean) => void
}

const SearchUiContext = createContext<Ctx | null>(null)

export function SearchUiProvider({ children }: { children: ReactNode }) {
  const [searchQuery, setSearchQuery] = useState('')
  const [overlayOpen, setOverlayOpen] = useState(false)
  const value = useMemo(
    () => ({
      searchQuery,
      setSearchQuery,
      overlayOpen,
      setOverlayOpen,
    }),
    [searchQuery, overlayOpen],
  )
  return <SearchUiContext.Provider value={value}>{children}</SearchUiContext.Provider>
}

export function useSearchUi() {
  const v = useContext(SearchUiContext)
  if (!v) {
    throw new Error('useSearchUi needs SearchUiProvider')
  }
  return v
}

