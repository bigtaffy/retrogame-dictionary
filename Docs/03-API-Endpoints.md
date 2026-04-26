# 03 — API Endpoints

> REST API spec。所有 endpoint 在 `/api/v1/` 之下。回應 JSON。

---

## 1. 認證

| 方式 | 用於 |
|---|---|
| **無認證** | 公開 read endpoint（GET /games, /search, ...） |
| **Bearer token** | 使用者寫入（投稿、修改個人資料、上傳圖） |
| **Bearer token + admin role** | 管理員 endpoint |

```http
Authorization: Bearer 1|abcdef0123456789...
```

token 透過 `POST /auth/login` 或 OAuth callback 取得。

### 認證 endpoints
| Method | Path | 說明 |
|---|---|---|
| POST | `/auth/register` | 註冊（email + password） |
| POST | `/auth/login` | 登入，回 token |
| POST | `/auth/logout` | 撤銷當前 token |
| POST | `/auth/forgot-password` | 寄重設信 |
| POST | `/auth/reset-password` | 用 token 重設 |
| GET | `/auth/oauth/google/redirect` | 跳轉 Google |
| GET | `/auth/oauth/google/callback` | Google 回呼，回 token |
| GET | `/auth/me` | 當前使用者資料 |
| PATCH | `/auth/me` | 更新個人資料 |

回應範例（`/auth/login`）：
```json
{
  "data": {
    "user": { "id": 12, "email": "...", "display_name": "Taffy", "roles": ["user"] },
    "token": "1|abcdef...",
    "expires_at": "2026-05-26T12:00:00Z"
  }
}
```

---

## 2. 公開讀取（Catalog）

### 2.1 列出主機
`GET /consoles`
```json
{
  "data": [
    { "id": 1, "slug": "pce", "name_zh": "PC Engine", "icon_url": "...", "game_count": 727 },
    { "id": 2, "slug": "gb",  "name_zh": "掌機", "icon_url": "...", "game_count": 803 },
    ...
  ]
}
```

### 2.2 列出遊戲（分頁 + 篩選 + 排序）
`GET /games`

Query params：
| 參數 | 範例 | 說明 |
|---|---|---|
| `console` | `pce` 或 `1` | 主機 slug 或 id |
| `genre` | `動作平台` | 類型 zh 名 或 slug |
| `region` | `日版` 或 `J` | 地區 |
| `format` | `HuCard` | 規格 |
| `rating` | `buyit` | 評等 |
| `letter` | `A` | A-Z bar |
| `q` | `mario` | 關鍵字（FULLTEXT） |
| `sort` | `-release_date` `title_zh` `-rating` | 多重排序，-=降序 |
| `page` | `2` | 頁碼（從 1 起） |
| `per_page` | `24` | 每頁筆數（max 100） |
| `include` | `images,videos` | 同時帶回關聯 |

回應：
```json
{
  "data": [
    {
      "id": 1234,
      "console": { "slug": "gba" },
      "slug": "metroid_fusion",
      "title_zh": "銀河戰士融合",
      "title_en": "Metroid Fusion",
      "title_jp": "メトロイドフュージョン",
      "maker": "Nintendo R&D1",
      "publisher": "Nintendo",
      "release_year": 2002,
      "format_category": "GBA",
      "region_category": "多區發行",
      "region_flags": "🇯🇵🇺🇸🇪🇺",
      "rating": "buyit",
      "cover": {
        "url": "https://r2.../covers/metroid_fusion.png",
        "thumb_url": "https://r2.../thumbs/metroid_fusion_240.png"
      }
    },
    ...
  ],
  "meta": {
    "page": 2,
    "per_page": 24,
    "total": 2745,
    "last_page": 115
  },
  "links": { "self": "...", "next": "...", "prev": "..." }
}
```

### 2.3 取得單一遊戲（完整詳細頁）
`GET /games/{id_or_slug}`
- 接受數字 id 或 `console-slug:game-slug`（例 `gba:metroid_fusion`）

回應：
```json
{
  "data": {
    "id": 1234,
    "console": { "slug": "gba", "name_zh": "..." },
    "titles": [
      { "language": "zh", "text": "銀河戰士融合", "is_aka": false },
      { "language": "en", "text": "Metroid Fusion", "is_aka": false },
      { "language": "jp", "text": "メトロイドフュージョン", "is_aka": false }
    ],
    "maker": "Nintendo R&D1",
    "publisher": "Nintendo",
    "release_year": 2002,
    "release_dates": { "jp": "2003-02-14", "na": "2002-11-17", "eu": "2002-11-22" },
    "genres": [
      { "slug": "action_adventure", "name_zh": "動作冒險", "is_primary": true }
    ],
    "regions": [
      { "code": "jp", "release_date": "2003-02-14" },
      { "code": "na", "release_date": "2002-11-17" }
    ],
    "format_category": "GBA",
    "rating": "buyit",
    "descriptions": [
      { "id": 99, "kind": "overview", "language": "zh", "text": "...", "source": "pce_bible", "is_primary": true },
      { "id": 100, "kind": "overview", "language": "en", "text": "...", "source": "pce_bible" },
      { "id": 101, "kind": "overview", "language": "en", "text": "...", "source": "wikipedia", "source_url": "https://en.wikipedia.org/wiki/..." }
    ],
    "images": [
      { "id": 4001, "kind": "cover", "url": "...", "region": "jp" },
      { "id": 4002, "kind": "title_screen", "url": "..." },
      { "id": 4003, "kind": "snap", "url": "..." }
    ],
    "videos": [
      { "id": 5001, "provider": "youtube", "external_id": "AiY__0DbEu4", "title": "..." }
    ],
    "external_links": {
      "wikipedia_en": "https://en.wikipedia.org/wiki/...",
      "baha": "https://acg.gamer.com.tw/..."
    },
    "view_count": 4823,
    "no_intro_name": "Metroid Fusion (USA, Europe)"
  }
}
```

### 2.4 搜尋（高速 + 自動完成）
`GET /search?q=mario&console=gba&limit=8`
- 用 Meilisearch / MySQL FULLTEXT
- 回最相關的 N 筆，含縮圖
- 也可以省略 `console`（跨主機搜尋）

回應：
```json
{
  "data": [
    {
      "id": 1234,
      "console": "gba",
      "slug": "metroid_fusion",
      "title_zh": "銀河戰士融合",
      "title_en": "Metroid Fusion",
      "thumb_url": "...",
      "score": 0.95
    },
    ...
  ]
}
```

### 2.5 隨機遊戲
`GET /random?console=pce&genre=動作平台`
- 回 1 筆 random
- 用於前端 🎲 按鈕

### 2.6 列出類型 / 地區 / 主機 metadata
- `GET /genres` — 所有類型
- `GET /regions` — 所有地區
- `GET /consoles/{slug}/genres` — 該主機有哪些類型（建篩選下拉用）
- `GET /consoles/{slug}/letters` — 該主機 A-Z 哪些有遊戲

---

## 3. 使用者寫入

### 3.1 投稿
`POST /contributions`（auth required）

Request body：
```json
{
  "type": "edit_field",
  "game_id": 1234,
  "target_field": "title_zh",
  "payload": { "new_value": "銀河戰士：融合" },
  "comment": "原翻譯漏掉冒號",
  "source_url": "https://zh.wikipedia.org/..."
}
```

回應 `201 Created`：
```json
{ "data": { "id": 999, "status": "pending", "created_at": "..." } }
```

可投稿的 type：
| type | 說明 |
|---|---|
| `add_game` | 新增遊戲（payload 是完整 game 物件） |
| `edit_field` | 修改某個欄位 |
| `add_translation` | 加一個語言的標題 / 簡介 |
| `add_image` | 上傳新圖（cover / 截圖 / marquee） |
| `add_video` | 加 YouTube 影片 |
| `report_error` | 純舉報，沒有具體修改建議 |

### 3.2 我的投稿
`GET /me/contributions?status=pending&page=1`
- 查自己投稿狀態
- 自己看得到 rejection_reason

### 3.3 撤回投稿（pending only）
`PATCH /contributions/{id}` body `{ "status": "withdrawn" }`

### 3.4 上傳附圖
`POST /contributions/{id}/attachments`
- multipart/form-data
- field: `file`（image/jpeg / image/png / image/webp，max 5 MB）
- 回應給 attachment id 跟暫存 URL

---

## 4. Admin endpoints（需 admin / moderator role）

所有 admin endpoint 路徑前綴 `/admin/`。

### 4.1 審核佇列
`GET /admin/contributions?status=pending&type=edit_field&console=gba&page=1`

```json
{
  "data": [
    {
      "id": 999,
      "user": { "id": 12, "display_name": "Taffy", "reputation": 23 },
      "game": { "id": 1234, "title_zh": "...", "console": "gba" },
      "type": "edit_field",
      "target_field": "title_zh",
      "payload": { "old_value": "舊", "new_value": "新" },
      "comment": "...",
      "source_url": "...",
      "created_at": "..."
    }
  ],
  "meta": { ... }
}
```

### 4.2 審核動作
`POST /admin/contributions/{id}/approve`
- body 可選：`{ "moderator_note": "..." }`
- 自動套用變更到 games / titles / descriptions / images
- 寫 audit_log

`POST /admin/contributions/{id}/reject`
- body：`{ "rejection_reason": "資料來源不可考", "moderator_note": "..." }`
- 通知投稿者（站內訊息 + email）

`POST /admin/contributions/{id}/duplicate`
- 標記為重複（連結到相關投稿 ID）

### 4.3 直接 CRUD（不走投稿）
管理員可以略過審核流程直接改：
- `POST /admin/games`
- `PATCH /admin/games/{id}`
- `DELETE /admin/games/{id}`
- `POST /admin/games/{id}/images`
- `POST /admin/games/{id}/videos`
- `POST /admin/games/{id}/descriptions`
- 所有變更都進 audit_log

### 4.4 使用者管理
- `GET /admin/users?q=email&role=contributor`
- `PATCH /admin/users/{id}/roles` — 升降權
- `POST /admin/users/{id}/ban` — 封鎖（含理由 + 期限）
- `POST /admin/users/{id}/unban`
- `GET /admin/users/{id}/activity` — 該 user 的所有投稿 / 登入紀錄

### 4.5 統計 / Dashboard
- `GET /admin/stats/overview` — 今日投稿數、PV、各主機進度
- `GET /admin/stats/contributors` — top 投稿者排行
- `GET /admin/stats/games-without-cover` — 缺資料 top
- `GET /admin/stats/api-usage` — API 流量

### 4.6 系統管理
- `GET /admin/settings` / `PATCH /admin/settings` — feature flags、配額
- `POST /admin/jobs/sync-libretro` — 手動觸發同步
- `POST /admin/jobs/rebuild-search-index`
- `GET /admin/audit-log?actor=12` — 查 log

---

## 5. 速率限制

| 範圍 | Limit | Header 回 |
|---|---|---|
| 未認證 | 60 req/min/IP | `X-RateLimit-Remaining` |
| 已認證 user | 600 req/min/user | 同上 |
| 投稿 | 20/天/user | `X-Contribution-Quota` |
| 圖片上傳 | 50/天/user, 5 MB/圖 | |
| Admin | 不限制 | |

超出時回 `429 Too Many Requests` + `Retry-After: <seconds>`

---

## 6. 版本 / 棄用

- 重大變更時推 `/api/v2/`，舊版維持 12 個月
- 即將棄用的 endpoint 加 `Deprecation: true` header + `Sunset: <date>`
- 整體變更記錄於 `CHANGELOG.md`

---

## 7. OpenAPI 文件

`l5-swagger` 會自動從 controller 註解產生：
```
GET https://api.dictionary.retrogame.works/api/documentation
```

開發者也可以下載 `openapi.json` 用 Postman / Insomnia 匯入。

---

## 8. 範例 client（前端 SPA 用）

```js
// services/api.js
const BASE = 'https://api.dictionary.retrogame.works/api/v1';
const token = localStorage.getItem('rgd-token');

async function api(path, opts = {}) {
  const res = await fetch(BASE + path, {
    ...opts,
    headers: {
      'Accept': 'application/json',
      ...(opts.body ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { 'Authorization': 'Bearer ' + token } : {}),
      ...opts.headers,
    },
  });
  if (!res.ok) throw new Error((await res.json()).error?.message);
  return res.json();
}

// 用法
const { data: games, meta } = await api(`/games?console=gba&page=2`);
const { data: game } = await api(`/games/gba:metroid_fusion`);
const submitted = await api('/contributions', {
  method: 'POST',
  body: JSON.stringify({ type: 'edit_field', game_id: 1234, ... })
});
```

---

## 9. 待決定

1. API 子網域是 `api.dictionary.retrogame.works` 還是 `dictionary.retrogame.works/api/v1`？
2. CORS：限制來源（只允許主站）還是開放全部？
3. ETag / Last-Modified：列表 endpoint 要不要支援？（前端 cache 友善）
4. GraphQL：未來要不要也提供？（給第三方開發者更彈性）
5. WebSocket：投稿狀態變更要即時推給前端嗎？（用 Pusher / Reverb）
