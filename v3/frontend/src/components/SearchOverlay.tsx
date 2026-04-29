import { useEffect, useId, useRef, useState } from 'react'
import { useSearchUi } from '../context/SearchUiContext'

export function SearchOverlay() {
  const { searchQuery, setSearchQuery, overlayOpen, setOverlayOpen } = useSearchUi()
  const [draft, setDraft] = useState(searchQuery)
  const id = useId()
  const inputRef = useRef<HTMLInputElement>(null)

  useEffect(() => {
    if (overlayOpen) {
      setDraft(searchQuery)
      const t = setTimeout(() => inputRef.current?.focus(), 50)
      return () => clearTimeout(t)
    }
  }, [overlayOpen, searchQuery])

  return (
    <div
      className={'search-overlay' + (overlayOpen ? ' show' : '')}
      id={id + '-so'}
      aria-hidden={!overlayOpen}
      onClick={(e) => {
        if (e.target === e.currentTarget) {
          setOverlayOpen(false)
        }
      }}
    >
      <button
        type="button"
        className="search-overlay-close"
        aria-label="Close"
        onClick={() => setOverlayOpen(false)}
      >
        ×
      </button>
      <div className="search-overlay-inner">
        <form
          className="search-wrap-overlay"
          onSubmit={(e) => {
            e.preventDefault()
            setSearchQuery(draft.trim())
            setOverlayOpen(false)
          }}
        >
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden>
            <circle cx="9" cy="9" r="6" stroke="currentColor" strokeWidth="2" />
            <path d="M14 14L18 18" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
          </svg>
          <input
            ref={inputRef}
            type="search"
            id="search-input-v3"
            placeholder="搜尋遊戲..."
            autoComplete="off"
            value={draft}
            onChange={(e) => setDraft(e.target.value)}
          />
        </form>
      </div>
    </div>
  )
}
