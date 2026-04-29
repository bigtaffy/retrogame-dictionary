import type { V2Game } from '../types/v2Game'
import { type ConsoleSlug } from '../config/consoles'

/**
 * 與 v2 `resolveImg` 行為相同（PCE 用 weserv 代理相對路徑圖片）
 * 遊戲清單改由 API 提供後，仍用於卡片／詳情顯示 URL。
 */
export function resolveV2Image(url: string, consoleName: ConsoleSlug): string {
  if (!url) {
    return ''
  }
  if (/^https?:\/\//i.test(url)) {
    return url
  }
  const imageBase = 'https://images.weserv.nl/?url=www.pcengine.co.uk/'
  const base = consoleName === 'pce' ? imageBase : ''
  if (!base) {
    return url
  }
  const p = url.replace(/^(\.\.\/)+/, '').replace(/^\.\//, '')
  return base + p
}

/**
 * 與 v2「Clear Cache」按鈕相容：v3 遊戲 JSON 已改由後端 API 提供，此處保留為 no-op
 *（避免 Settings 因 import 不存在的靜態快取而壞掉）。
 */
export function clearV2JsonCache(): void {
  // 保留給日後若再加瀏覽器端快取
}

/**
 * 若從靜態 JSON 讀入時可呼叫（目前清單皆走 API，通常不會用到）
 */
export function normalizeGameImagesForV2View(games: V2Game[], consoleName: ConsoleSlug): V2Game[] {
  for (const g of games) {
    if (g.cover) {
      g.cover = resolveV2Image(g.cover, consoleName)
    }
    if (Array.isArray(g.screenshots)) {
      g.screenshots = g.screenshots.map((s) => {
        if (typeof s === 'string') {
          return resolveV2Image(s, consoleName)
        }
        if (s && typeof s === 'object' && s !== null && 'url' in s) {
          const o = s as { url: string; [k: string]: unknown }
          if (typeof o.url === 'string') {
            return { ...o, url: resolveV2Image(o.url, consoleName) }
          }
        }
        return s
      }) as V2Game['screenshots']
    }
  }
  return games
}
