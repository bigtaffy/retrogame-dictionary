import type { V2Game } from '../types/v2Game'

export function pickTitle(g: V2Game): string {
  return g.title_zh || g.title_en || g.title_jp || '?'
}

export function langClass(lang: 'zh' | 'en' | 'jp'): string {
  if (lang === 'zh') {
    return 'text-zh'
  }
  if (lang === 'jp') {
    return 'text-jp'
  }
  return 'text-en'
}

export function detectLang(g: V2Game): 'zh' | 'en' | 'jp' {
  if (g.title_zh) {
    return 'zh'
  }
  if (g.title_en) {
    return 'en'
  }
  if (g.title_jp) {
    return 'jp'
  }
  return 'zh'
}

export function fmtCategory(
  c: string,
  formatRaw: string,
): { cls: string; label: string } | null {
  if (!c && !formatRaw) {
    return null
  }
  const v = (c || formatRaw || '').toLowerCase()
  if (v.includes('hu')) {
    return { cls: 'format-hucard', label: 'HuCard' }
  }
  if (v.includes('scd') || v.includes('super cd')) {
    return { cls: 'format-scd', label: 'Super CD' }
  }
  if (v.includes('cd')) {
    return { cls: 'format-cd', label: 'CD-ROM²' }
  }
  if (v.includes('arcade')) {
    return { cls: 'format-arcade', label: 'Arcade' }
  }
  if (v.includes('sg')) {
    return { cls: 'format-sg', label: 'SuperGrafx' }
  }
  if (v.includes('cart')) {
    return { cls: 'format-cart', label: 'Cartridge' }
  }
  if (v.includes('disk') || v.includes('disc')) {
    return { cls: 'format-disk', label: 'Disk' }
  }
  return { cls: 'format-other', label: formatRaw || c }
}

export function ratingBadge(r: string): { cls: string; label: string } | null {
  if (!r) {
    return null
  }
  const x = r.toLowerCase().replace(/\s+/g, '')
  if (x.includes('buy')) {
    return { cls: 'rating-buyit', label: 'BUY IT' }
  }
  if (x.includes('try')) {
    return { cls: 'rating-tryit', label: 'TRY IT' }
  }
  if (x.includes('avoid')) {
    return { cls: 'rating-avoid', label: 'AVOID' }
  }
  return { cls: 'rating-unrated', label: 'UNRATED' }
}
