/**
 * 與 v2 專案 data/games.json 單筆物件相同欄位（PCE）。
 */
export interface V2Game {
  id: string
  letter: string
  title_en: string
  title_jp: string
  title_zh: string
  aka: string
  maker: string
  release_date: string
  style: string
  format: string
  rating: string
  overview_en: string
  overview_zh: string
  comment_en: string
  comment_zh: string
  cover: string
  source_url: string
  format_category: string
  screenshots: string[]
  genre_category: string
  youtube: string[]
  no_intro_name: string
  /** 部分主機（如 GBA） */
  region_category?: string
  year?: string
  /** 詳情頁可選 */
  developer?: string
  publisher?: string
  price?: string
  media?: string
  product_code?: string
  fc_no?: string
  wiki_title?: string
  wiki_extract_en?: string
  /** 多區版本 */
  regions?: string[]
  /** 部分主機（如 GBA/MD） */
  region_flags?: string
}

export interface GamesListResponse {
  data: V2Game[]
  meta: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export interface GameDetailResponse {
  data: V2Game
}
