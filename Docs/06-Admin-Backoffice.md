# 06 — Admin Backoffice（管理員後台）

> 管理員看的儀表板與工具集合。盡量讓常用操作 1-2 click 完成。

> 設計原則：**所有頁面都優先快取 + 分頁，避免單頁重 query 拖慢系統**（呼應 server loading 原則）。

---

## 1. 後台網址

`https://admin.dictionary.retrogame.works`（獨立子網域）

> 為什麼獨立子網域：CSP / Cookie / Rate-limit policy 可分開，被攻擊時不影響主站。

進入需 admin / moderator role + 2FA（建議 TOTP）。

---

## 2. 主選單（Filament v3 sidebar）

```
🏠 Dashboard

📥 Moderation
   ├─ Pending queue        ← 連 05-Admin-Moderation
   ├─ My assigned
   └─ Recently approved/rejected

🎮 Catalog
   ├─ Games
   │   ├─ All games (table)
   │   ├─ Quick add
   │   └─ Batch import
   ├─ Consoles
   ├─ Genres
   ├─ Regions
   └─ Images library

👥 Users
   ├─ All users (table)
   ├─ Roles & permissions
   ├─ Banned
   └─ Top contributors

📊 Stats
   ├─ Overview (KPIs)
   ├─ Contributions analytics
   ├─ Site traffic (PV/UV)
   ├─ API usage
   └─ Coverage gaps (缺資料 top)

🔧 System
   ├─ Settings (feature flags)
   ├─ External APIs (SS / Wiki keys)
   ├─ Jobs / Schedules
   ├─ Audit log
   └─ Cache control
```

---

## 3. Dashboard（首頁）

進來第一個畫面：把今天最重要的 5 個數字 + 3 個 alarm 擺出來。

```
┌──────────────────────────────────────────────────────────────────────┐
│  Hello, Bob 👋                                          2026-04-26   │
│                                                                      │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │
│  │ 待審      │ │今日通過  │ │今日駁回  │ │活躍用戶  │ │API call │  │
│  │  47      │ │  12      │ │  3       │ │ 89       │ │ 14.2K   │  │
│  │ ⏳        │ │ ✓ +9 vs │ │ ✗        │ │ 👥       │ │ 📊       │  │
│  │           │ │ yest     │ │           │ │          │ │          │  │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘  │
│                                                                      │
│  ⚠️  Alarms                                                          │
│  • 5 個投稿超過 24h 未審   [前往佇列]                                │
│  • DB slow query ↑ 30%（過去 1h）  [查看 Telescope]                  │
│  • Cloudflare R2 用量 79%  [查看儲存]                                │
│                                                                      │
│  📈 Last 7 days                                                      │
│  Contributions ▕▏▕▏▕▏▕▏▕▏▕▏▕▏  PV ▕▏▕▏▕▏▕▏▕▏▕▏▕▏                  │
│                                                                      │
│  🏆 Top contributors (this month)                                   │
│  1. Taffy   23 投稿 (96% 通過率)                                     │
│  2. Alice   18 投稿 (89%)                                            │
│  3. Bobby   12 投稿 (75%)                                            │
└──────────────────────────────────────────────────────────────────────┘
```

所有方塊**只用 cached counter**（每 5 分鐘 background refresh，前端不打 DB）。

---

## 4. Catalog 管理

### 4.1 All Games（list）

Filament data table：
- 預設 column：cover thumb, title_zh, title_en, console, year, genre, last_updated
- Filter：console, genre, region, missing fields, no cover, no description
- Sort：col header click
- Action 列：Edit / Delete / View on site / Direct edit

「Missing fields」欄位特別重要：點 → 直接跳該遊戲的編輯頁，欄位 highlight 缺失項。

### 4.2 Quick Add（單筆新增）

最少必填：
- Console 下拉
- Title (en + zh + jp 至少一個)
- Year
- Maker

按下 save 後跳到完整編輯頁（補圖、影片、簡介…）。

### 4.3 Batch Import

CSV / JSON 上傳，後端 dry-run preview：

```
┌───────────────────────────────────────────────┐
│ Upload CSV                                    │
│ [選擇檔案] sonic_collection.csv               │
│                                               │
│ Preview                                       │
│ ✓ 12 games will be added                     │
│ ⚠ 3 games conflict with existing slugs      │
│   - mario_party (existing id 1234)          │
│     [skip] [overwrite] [merge]              │
│ ✗ 1 row has invalid console value           │
│                                               │
│ [取消]                              [匯入]   │
└───────────────────────────────────────────────┘
```

### 4.4 Images library

所有圖片（含 user uploads pending）的 grid view：
- 篩 source = libretro / r2_user / r2_admin
- 點圖 → 顯示用在哪些遊戲（reverse lookup）
- 批次刪除孤兒圖（沒被任何 game reference）

---

## 5. Users 管理

### 5.1 All users 表

- 預設欄位：display_name, email, roles, reputation, contrib_total, contrib_approved%, last_login
- Filter：role, banned status, registered_after
- Action：「升 contributor」「降 user」「ban」「impersonate」（debug 用，walk-through 投稿者體驗）

### 5.2 User detail

```
┌──────────────────────────────────────────────┐
│  Taffy  (id 12)                              │
│  bigtaffy@gmail.com (verified)              │
│  Roles: contributor, user                    │
│  Reputation: 38                              │
│  ──────────────────                         │
│  📊 統計                                     │
│  總投稿: 47   通過: 38 (81%)   駁回: 3       │
│  最後登入: 5 小時前 (220.140.x.x)            │
│  最後投稿: 2 小時前                          │
│                                              │
│  📜 投稿歷史                                  │
│  [跳轉投稿列表]                              │
│                                              │
│  📜 操作 log                                  │
│  - 2026-04-26 14:23  submitted #1024        │
│  - 2026-04-26 09:01  logged in              │
│                                              │
│  🛠 Actions                                   │
│  [升 moderator] [Ban...] [Impersonate]      │
└──────────────────────────────────────────────┘
```

### 5.3 Ban dialog

```
原因（必填）：[__________]
期限：[ 7 天 ▾ ]  (1d / 7d / 30d / 永久)
通知 user？：[v]
[取消]            [確認 Ban]
```

Ban 後 user 還能登入但所有寫入 endpoint 拒絕。

---

## 6. Stats / Analytics

### 6.1 Overview

時間範圍：過去 24h / 7d / 30d / 自訂

KPI：
- DAU / MAU
- 投稿數（pending / approved / rejected）
- API requests（total / by endpoint / 4xx / 5xx 比率）
- DB queries / 平均 query 時間
- 平均審核時間

### 6.2 Contributions analytics

- 投稿類型 pie chart（edit_field 60% / add_image 25% / ...）
- 每天投稿趨勢（line chart）
- Top contributors（leaderboard）
- 每個主機的覆蓋率變化（這個月 GBA cover 從 88% → 99%）

### 6.3 Coverage gaps

特別重要的頁面，列出**目前還缺什麼**：

```
Coverage Gaps
─────────────
GBA · 缺中文標題：512 款 (18.7%)  [exports CSV]
PCE · 缺日文標題：23 款            [exports CSV]
FC  · 缺封面：897 款 (72%)         [exports CSV]
GBC · 缺簡介：776 款 (100%)        [exports CSV]
...
```

點 export → 拿 CSV 給社群當投稿任務清單。

### 6.4 API usage

- 每分鐘 RPS
- Top endpoints（哪個被打最多）
- 各 user 的呼叫量（找出可疑爬蟲）
- Cache hit rate（>90% 為健康）

---

## 7. System / Tools

### 7.1 Settings（feature flags）

```
[v] contributions enabled
[v] OAuth Google
[v] OAuth Discord
[ ] AI auto-translation
[ ] ScreenScraper sync   (key not set)
[ ] Maintenance mode

Limits:
  contributions/user/day: [20]
  max upload mb:          [5]
  rate limit guest /min:  [60]
  rate limit auth /min:   [600]
```

### 7.2 External APIs

```
ScreenScraper
  devid:        ●●●●●●●●●  [edit]
  devpassword:  ●●●●●●●●●  [edit]
  Today usage: 0 / 20000

Wikipedia
  rate limit policy: standard (no key needed)

Google OAuth
  client_id:   ●●●●●●●●●  [edit]
  client_secret: ●●●●●●●●●

[Save]
```

### 7.3 Jobs / Schedules

列出排程 + 手動觸發：
```
sync_libretro_thumbnails      Last run: 3 days ago    [Run now]
cache_reload_popular_games    Last run: 1 hour ago    [Run now]
cleanup_rejected_contributions Last run: 1 day ago    [Run now]
```

連到 Horizon dashboard 看 queue 狀態。

### 7.4 Audit log

跟 05-Moderation.md 那段一樣，多了 admin-side 篩選：

- Filter actor / subject / action / date
- Export CSV

### 7.5 Cache control

```
Cache hit rate (last hour): 91.3%

Hot keys:
  games:list:gba:p1:per24    → 234 hits/min
  games:detail:1234           → 87 hits/min
  search:mario:limit10        → 56 hits/min

Actions:
  [Flush all]   [Flush games]   [Flush search]   [Flush stats]
```

「Flush all」要二次確認（會造成短暫 DB 壓力暴增）。

---

## 8. 通知 / 收件匣

每個 mod 有後台 inbox：

- 新投稿 ping（依設定可關）
- 系統 alarm（DB slow / disk full / job failed）
- 升降權通知（其他 admin 改變你的權限）

可設「digest」每天一次而非即時。

---

## 9. 多 admin 協作功能

- **任務板**：admin 可指派任務給彼此（「Bob，請補 GBA 缺中文 512 款」）
- **已讀記號**：每張 contribution 的 detail 頁顯示「Bob viewed 5 min ago」
- **內部留言**：每張 contribution / game / user 頁有「mod note」（only admins see）

---

## 10. 安全 / 操作審計

| 操作 | 需要 | 紀錄 |
|---|---|---|
| 一般 approve / reject | mod role | audit_log |
| Ban user | admin role | audit_log + email user |
| Direct delete game | admin role + 2FA confirm | audit_log + Slack notification |
| Bulk operation > 50 筆 | admin role + 2FA | audit_log + 限速 |
| Change settings | admin role | audit_log + email all admins |
| Impersonate user | admin role + reason | audit_log（含 reason） |

---

## 11. 行動裝置適配

後台主要桌機用，但行動 read-only：
- Dashboard（看 alarm / 數字）
- Pending queue（瀏覽）
- 投稿單筆審核（approve / reject）

行動端不顯示批次操作 / settings / catalog 編輯（避免誤觸）。

---

## 12. 開放問題

1. **後台 UI 用 Filament 還是自寫？**
   - 建議 Filament（v1 開箱即用，v2 想客製再 fork）。

2. **2FA 是否強制？**
   - 建議 admin 強制 TOTP，moderator 可選但鼓勵。

3. **資料匯出 CSV 限制？**
   - 防外洩：超過 10,000 row 需 admin 批准，限頻率 10 次/天/user。

4. **管理員之間的權限差異需要多細？**
   - v1：admin / moderator / user 三層即可。
   - 未來可加 super-admin（只能給 admin role 的人）。

5. **是否要做「公開的編輯歷史頁」給所有人看？**
   - 建議 v2 再加，類似 Wikipedia 的「View history」。
