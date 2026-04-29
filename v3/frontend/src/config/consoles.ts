/**
 * 與倉庫根目錄 `app.html` 的 CONSOLE_CONFIG 一致（路由與 v2 靜態 JSON 位址）
 */
export const CONSOLE_CONFIG = {
  pce: {
    name: 'PC Engine',
    dataPath: 'games.json' as const,
    hasFormat: true,
    hasRegion: false,
    hasYoutube: true,
  },
  gba: {
    name: 'Game Boy Advance',
    dataPath: 'gba/games.json' as const,
    hasFormat: false,
    hasRegion: true,
    hasYoutube: false,
  },
  fc: {
    name: 'Famicom',
    dataPath: 'fc/games.json' as const,
    hasFormat: true,
    hasRegion: false,
    hasYoutube: false,
  },
  gb: {
    name: 'Game Boy',
    dataPath: 'gb/games.json' as const,
    hasFormat: false,
    hasRegion: true,
    hasYoutube: false,
  },
  gbc: {
    name: 'Game Boy Color',
    dataPath: 'gbc/games.json' as const,
    hasFormat: false,
    hasRegion: true,
    hasYoutube: false,
  },
  md: {
    name: 'Mega Drive',
    dataPath: 'md/games.json' as const,
    hasFormat: true,
    hasRegion: true,
    hasYoutube: false,
  },
} as const

export type ConsoleSlug = keyof typeof CONSOLE_CONFIG

export function isConsoleSlug(s: string): s is ConsoleSlug {
  return s in CONSOLE_CONFIG
}

/** 與 v2 頂欄圖示順序一致 */
export const CONSOLE_ORDER: ConsoleSlug[] = ['pce', 'gb', 'gbc', 'gba', 'fc', 'md']
