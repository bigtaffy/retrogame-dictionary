import { useCallback, useEffect } from 'react'

const TEXT_SIZES = [
  { key: 'M', scale: 1.0, label: 'M' },
  { key: 'L', scale: 1.12, label: 'L' },
  { key: 'XL', scale: 1.28, label: 'XL' },
  { key: '2XL', scale: 1.45, label: '2XL' },
] as const

function applyTextSize(key: string) {
  const def = TEXT_SIZES.find((s) => s.key === key) ?? TEXT_SIZES[1]
  document.documentElement.style.setProperty('--text-scale', String(def.scale))
  document.documentElement.dataset.textSize = def.key
  try {
    localStorage.setItem('rgd-text-size', def.key)
  } catch {
    // ignore
  }
}

export function useTextSizeInit() {
  useEffect(() => {
    try {
      applyTextSize(localStorage.getItem('rgd-text-size') || 'L')
    } catch {
      applyTextSize('L')
    }
  }, [])
}

export function useCycleTextSize() {
  return useCallback(() => {
    let cur = 'L'
    try {
      cur = localStorage.getItem('rgd-text-size') || 'L'
    } catch {
      // ignore
    }
    const idx = TEXT_SIZES.findIndex((s) => s.key === cur)
    const next = TEXT_SIZES[(idx + 1) % TEXT_SIZES.length]
    applyTextSize(next.key)
  }, [])
}
