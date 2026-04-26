# 07 — Performance & Caching（效能 / 快取策略）

> **核心原則：使用者瀏覽動作不應該頻繁打 server。**
>
> 寫在最前面：本文件是上述設計（DB schema、API、UI）的橫切面（cross-cutting concern）。所有功能都要遵守這裡的策略。

---

## 1. 整體設計哲學

```
                 [Cloudflare CDN]
                       │
          ┌────────────┼────────────┐
          │            │            │
      [瀏覽器快取]  [CDN edge cache]  [origin Laravel + Redis]
          │            │            │
       0 hop       <50ms hop     後端最後手段
          │            │            │
       ★ ideal      acceptable    fallback
```

**目標**：
- 80%+ 請求在 CDN 命中，**不打到 origin**
- origin 命中時，90%+ 在 Redis 拿到，**不打 MySQL**
- MySQL 只在第一次或資料變更時查
- 使用者瀏覽 100 款遊戲應該 **打不到 5 次 origin**

---

## 2. 多層快取

### Layer 1：HTTP cache headers（瀏覽器 + CDN 都用）

| Endpoint | Cache-Control |
|---|---|
| `GET /consoles` | `public, max-age=86400` (1 天，幾乎不變) |
| `GET /games/{id}` | `public, max-age=300, s-maxage=3600, stale-while-revalidate=86400` |
| `GET /games?...` | `public, max-age=60, s-maxage=300` |
| `GET /search?q=...` | `public, max-age=60` |
| `GET /me/...` | `private, no-cache` (使用者特定) |
| `POST /...` | `no-store` |

**ETag**：所有 GET 回 ETag（hash of body）。瀏覽器下次帶 `If-None-Match`，server 比對沒變回 `304 Not Modified`，body 0 bytes。

### Layer 2：Cloudflare CDN

- API 子網域走 CF proxy（橘雲）
- 開「Cache Everything」+ Page Rules
- CF Worker 可在 edge 攔截、做 stampede protection

**stale-while-revalidate**：CDN 看到 `s-maxage` 過期但仍在 SWR 範圍內，**先回舊版給使用者**（0 latency），背景去 origin 拿新的。

### Layer 3：Redis（origin cache）

`config/cache.php` 用 Redis driver。Laravel：

```php
// 快取單筆遊戲詳情 1 小時
$game = Cache::remember("game:{$id}:full", 3600, fn() =>
    Game::with('titles', 'descriptions', 'images', 'videos')->findOrFail($id)
);
```

Cache key 規範：`game:{id}:full`、`games:list:{console}:{filters_hash}:p{page}`、`search:{query_hash}`...

### Layer 4：DB query cache（MySQL）

MySQL 8 已移除 query cache，改靠：
- buffer pool 大（建議 4 GB+）
- 適當索引
- read replica（流量大時）

---

## 3. 失效策略（cache invalidation）

「資料變了之後快取怎麼辦」是難題。策略：

### Approve contribution → 套用變更

```
ApplyContribution job:
  1. UPDATE games SET ... WHERE id = $id
  2. Cache::forget("game:{$id}:full")
  3. Cache::tags(['games_list'])->flush()  // 列表頁可能順序變
  4. CF API: purge cache for /api/v1/games/{id}
  5. CF API: purge cache for /api/v1/games?console={...}
```

用 Cache tags（[`spatie/laravel-tags-on-cache`] 或 Redis SET 集合）一鍵失效相關 keys。

### CDN purge

- 用 Cloudflare Cache Purge API（按 URL 或 prefix）
- 批次操作只 purge 一次（用 debounce 5 秒）

---

## 4. 前端最佳實踐

### 4.1 一進站只打 1-2 個 API

```
頁面初始化：
  GET /consoles                ← 5 個主機（cached forever）
  GET /me                      ← 是否登入（auth only）
  GET /games?console=pce&page=1 ← 第一頁
```

完成後 SPA 把所有 metadata（genres、letters、formats）跟著塞進 console list 回應一次拿。

### 4.2 後續操作不再打 API（除非必要）

- 切主機：load `data:games?console=gba` 一次，**整個 console 的列表進記憶體**（Map）。後續篩 / 排序純前端。
- 篩 genre / format / region：純前端 filter。
- 跳頁：純前端 paginate。
- A-Z：純前端 filter。
- 切換 view（grid / list / covers）：純前端，0 request。

### 4.3 前端記憶體 + LocalStorage

```
首次切到 GBA：
  fetch /games?console=gba&fields=light&include=images
    → 拿到 2745 筆 (gzipped ~500KB, decompressed ~2MB)
  saveToMemory(state.cache.gba)
  saveToLocalStorage('cache:gba:v=ETAG', data)

下次切到 GBA：
  if (localStorage.has('cache:gba')) {
    state.cache.gba = JSON.parse(localStorage.get(...))
    使用 ETag 對 If-None-Match 打 origin → 304 不重抓
  }
```

LocalStorage limit ~5MB per origin。5 個主機若全 cache，要小心不要爆。可改用：
- IndexedDB（無上限）
- Service Worker + Cache Storage API

### 4.4 詳細頁

`GET /games/{id}` 一次拿完整資料（含關聯）。前端進到 detail 後：
- 圖片 lazy load（IntersectionObserver）
- YouTube iframe 點才載
- 螢幕外圖片不下載

點返回 → list view → detail 已 cache 過再點不再打 API。

### 4.5 搜尋自動完成

- Debounce 200-300ms（user 還在打字不送）
- 限 query 字數 ≥ 2
- 結果 cache 在 SessionStorage（同 session 重複關鍵字不重打）
- 後端 Meilisearch 回傳速度 < 50ms

---

## 5. 圖片 / 靜態資源

### 5.1 用 Cloudflare R2 + Cloudflare Images（或 Bunny.net）

- 全部 cover / 截圖傳 R2
- CF Image Resizing 自動產 thumb（240w, 480w, 1200w）
- WebP / AVIF 自動轉換（瀏覽器支援時）
- HTTP/2 push + early hints
- Cache-Control: `public, max-age=31536000, immutable`（永久）

### 5.2 響應式圖片

```html
<img
  src="/r2/cover/1234@480w.webp"
  srcset="
    /r2/cover/1234@240w.webp 240w,
    /r2/cover/1234@480w.webp 480w,
    /r2/cover/1234@1200w.webp 1200w"
  sizes="(max-width: 600px) 50vw, 240px"
  loading="lazy"
  decoding="async"
>
```

### 5.3 影片預覽

- YouTube thumbnail 直接連 `img.youtube.com/vi/{id}/hqdefault.jpg`（free, fast CDN）
- 不一進 detail 就 embed iframe，**等 user 點再 swap**

---

## 6. API 設計面（讓快取效率高）

### 6.1 不要因 user 而變的回應，用 public cache

例：`GET /games/{id}` 同樣 ID 回同樣內容，給所有 user → public cache。
不要在這個 endpoint 摻入 `is_favorited_by_me` 之類的欄位（會破壞快取）。

### 6.2 user-specific 資料分開

`GET /me/favorites` 是 private 的，獨立 endpoint。不要塞進 catalog。

### 6.3 列表分頁限定 max_per_page

`per_page=10000` 讓 CDN cache 1 個巨型回應沒意義（很少人用）。限 max=100。

### 6.4 不要 N+1 暴露

API resource serializer 要 eager-load 所有關聯，避免 1 個請求進去後端打 100 次 SQL。

---

## 7. Database 層

### 7.1 索引（已在 02-Database-Schema 詳述）

### 7.2 read replica（流量大時）

- 主寫，副讀
- Laravel 設 `DB_HOST_READ` / `DB_HOST_WRITE`
- 讀寫分離後 reads 可橫向擴展

### 7.3 物化視圖（materialized view）

頻繁 group by 的統計（top contributors、genre 統計）做成快取表，每 5 分鐘 refresh，admin dashboard 從這邊讀。

---

## 8. 防爆量機制

### 8.1 Rate limit

| 範圍 | 限制 |
|---|---|
| Guest（IP） | 60 req/min |
| Auth user | 600 req/min |
| 投稿 | 20/day（業務上限） |
| 圖片上傳 | 50/day |
| Search | 30/min（Meilisearch 撐不住太頻繁） |

超過 → 429 + Retry-After

### 8.2 機器人 / 爬蟲

- 偵測：UA 黑名單、req pattern（同 IP 100 件/秒）、無 cookie
- 對應：Cloudflare Bot Fight Mode、challenge page

### 8.3 Cache stampede（快取雪崩）

熱門遊戲 cache 過期瞬間 1000 人打進來→1000 個 SQL query。

防：
- **Probabilistic early expiry**：cache 過期前 10% 時間，1% 機率提前刷新
- **Lock**：`Cache::lock("rebuild:game:{$id}")->get(...)` 同時只一個 worker rebuild
- **stale-while-revalidate**：永遠回舊版，背景刷

---

## 9. 監控指標

| 指標 | 目標 |
|---|---|
| API p50 latency | < 50ms |
| API p95 latency | < 200ms |
| API p99 latency | < 500ms |
| Cache hit rate (Redis) | > 90% |
| CDN hit rate | > 80% |
| MySQL slow queries / day | < 100 |
| Origin RPS | < 200 (with 5,800 games) |

工具：
- Cloudflare Analytics（CDN 命中、bandwidth）
- Laravel Pulse（內建監控）
- Sentry（錯誤）
- 自製 dashboard 拉 admin 看

---

## 10. 預估容量

5,800 games + 10,000 daily active users 的初估：

| 資源 | 估值 |
|---|---|
| 每日 PV | 50,000 |
| 每日 API call | ~150,000（每 PV 3 calls） |
| 命中 origin | ~30,000（80% CDN 命中） |
| 命中 MySQL | ~3,000（90% Redis 命中） |
| 平均 query/sec on MySQL | < 1 |
| 平均 RPS on origin | ~0.3 |

**結論：1 台中等 VPS（4 vCPU / 8 GB RAM）綽綽有餘。** 真正大流量時瓶頸是 image bandwidth（用 R2 解決），不是 API origin。

---

## 11. 反模式（避免）

❌ **每次 user 動作都打 API**
- 切篩選下拉、切 view、跳頁 → 不應該打 server
- 改：first-load 一次拿全主機，後續純前端

❌ **詳細頁打 N 個 endpoint**
- 不要 `/games/{id}` + `/games/{id}/images` + `/games/{id}/videos` 分開
- 改：1 個 endpoint 帶所有關聯

❌ **長輪詢（long polling）**
- 不要前端每 5 秒打一次「我的投稿狀態」
- 改：approve 時 server 推（WebSocket / Pusher）或 user 自己重整

❌ **沒分頁的列表**
- `GET /games` 不能回 5,800 筆
- 改：強制 `per_page` 上限

❌ **沒 ETag 的詳細頁**
- 沒 ETag → 即使 body 沒變也要送 1MB
- 改：所有 GET 都帶 ETag

❌ **CDN 後再 personalize**
- 在 CDN 層回應後，前端改其中一兩欄（例如 favorited star）
- 改：兩個 endpoint，public 跟 private 分開

---

## 12. 驗收檢查表

每加新 feature，問自己：

- [ ] 此 endpoint 能 cache 嗎？
- [ ] Cache TTL 設多久？
- [ ] 寫入時哪些 cache key 要 invalidate？
- [ ] 前端能否 reuse 已 fetch 的資料？
- [ ] 是否會被機器人濫用？是否限流？
- [ ] 容量：100 倍流量會不會炸？

---

## 13. 開放問題

1. **要 PWA + Service Worker 嗎？** 可離線瀏覽。建議 v2 加。
2. **要 GraphQL 嗎？** 第三方 client 需要彈性查詢時。建議 v3 加（v1 不做）。
3. **CDN 要 Cloudflare 還是 Bunny / Fastly？** Cloudflare 免費 tier 夠（API 流量小）；圖片走 Bunny CDN（按量更便宜）也可。
4. **是否要 Edge SSR（Workers）？** 第一頁 SEO 友善。建議優先級低。
