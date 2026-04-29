import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react'

type Ctx = {
  open: (node: ReactNode) => void
  close: () => void
}

const LightboxContext = createContext<Ctx | null>(null)

export function LightboxProvider({ children }: { children: ReactNode }) {
  const [content, setContent] = useState<ReactNode>(null)
  const open = useCallback((node: ReactNode) => setContent(node), [])
  const close = useCallback(() => setContent(null), [])
  const value = useMemo(() => ({ open, close }), [open, close])
  return (
    <LightboxContext.Provider value={value}>
      {children}
      <div
        className={'lightbox' + (content ? ' show' : '')}
        role="dialog"
        aria-modal="true"
        onClick={(e) => {
          if (e.target === e.currentTarget) {
            close()
          }
        }}
      >
        <button type="button" className="close" onClick={close} aria-label="關閉">
          ×
        </button>
        <div id="lightbox-content">{content}</div>
      </div>
    </LightboxContext.Provider>
  )
}

export function useLightbox() {
  const v = useContext(LightboxContext)
  if (!v) {
    throw new Error('useLightbox needs LightboxProvider')
  }
  return v
}
