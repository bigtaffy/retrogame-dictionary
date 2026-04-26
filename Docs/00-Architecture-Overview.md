# 00 — Architecture Overview

> 復古遊戲字典 v3 — Server / Client 架構總覽
> 撰寫日：2026-04-26

---

## 1. 從現況到目標

### 現況（v2，目前線上）
```
[Cloudflare Pages] ── 靜態 SPA (app.html)
       │
       └── data/
            ├── games.json     (PCE, 1.3 MB)
            ├── gba/games.json (4.0 MB)
            ├── fc/games.json  (1.2 MB)
            ├── gb/games.json  (530 KB)
            └── gbc/games.json (540 KB)
```

- 純前端，瀏覽器直接 fetch JSON
- 改資料 → 直接編 JSON → git push → Cloudflare 自動 deploy
- 沒有使用者帳號、沒有寫入功能
- 5,800+ 款遊戲、~7 MB 資料量
- 部署簡單、零維運成本

### 目標（v3）
```
                 ┌───────────────────────────────────────┐
[Cloudflare Pages] ── SPA (HTML/JS, 不變或 Vue/React 重寫)
       │              │
       └────── HTTPS API ──────► [Laravel 後端]
                                    │
                                    ├── REST API (read + write)
                                    ├── Auth (email + OAuth)
                                    ├── 投稿 / 審核工作流程
                                    └── Admin 後台
                                          │
                                          ▼
                                  [MySQL 8.x]
                                     資料 + 投稿 + 使用者
```

- Backend：Laravel 11 + PHP 8.3 + MySQL 8
- API 風格：JSON REST（OpenAPI 3 規範）
- 前端維持 SPA，改成從 API fetch 而不是靜態 JSON
- 新增：使用者註冊、投稿、管理員審核、後台統計

---

## 2. 為什麼換架構

| 痛點（現在） | v3 解法 |
|---|---|
| 前端載入 4–7 MB JSON 才能搜尋 | API 分頁 + 條件查詢，每次只載必要的 |
| 改一筆資料要 git push + redeploy | API 寫入即時生效，不重 build |
| 沒辦法讓社群補資料 / 改錯字 | 使用者投稿 + 管理員審核 |
| 統計、舉報、使用者行為無法追蹤 | DB log + admin 後台 |
| 無法搜尋全文 / 模糊比對 | MySQL FULLTEXT 或 Meilisearch |
| 無法對接外部資料源 reactively | 後端排程 job 抓 ScreenScraper / Wiki |

---

## 3. 技術選型

### Backend：Laravel 11
| 元件 | 用途 |
|---|---|
| **Laravel 11** | PHP web framework，Eloquent ORM、Queue、Scheduler |
| **MySQL 8.x** | 主資料庫，FULLTEXT 中文支援 + utf8mb4 |
| **Redis 7** | session、cache、queue backend |
| **Laravel Sanctum** | API token auth（前端 + 第三方 client） |
| **Laravel Filament** *(可選)* | 管理後台快速搭建（Admin Panel） |
| **Laravel Horizon** | Queue dashboard |
| **Laravel Telescope** *(dev only)* | 開發期除錯儀表板 |
| **Spatie Media Library** | 上傳圖片（投稿封面 / 截圖）管理 |
| **Spatie Permission** | RBAC（user / contributor / moderator / admin） |
| **L5-Swagger** | 自動產 OpenAPI 文件 |

### Frontend：選一個
| 選項 | Pros | Cons |
|---|---|---|
| **A. 維持 vanilla HTML/JS**（app.html 改 fetch API） | 零學習曲線、bundle 0 KB | SPA 大、不易加複雜功能 |
| **B. 重寫成 Vue 3 + Vite** | TypeScript、組件化、Pinia 狀態管理 | 重寫 |
| **C. Inertia.js + Vue**（Laravel 官方推薦） | 後端 routing 統一、SSR 友善 | 跟 SPA 解耦差 |

**建議：A 短期過渡 → B 長期重寫**

### Storage / 部署
| 層 | 服務 |
|---|---|
| Backend host | DigitalOcean / Linode / AWS EC2（Laravel Forge 管理） |
| MySQL | 同上 host 或 RDS / DO Managed DB |
| Static assets（封面 / 截圖） | Cloudflare R2（S3 相容、便宜、無 egress 費） |
| CDN | Cloudflare（front of API + R2） |
| Frontend | Cloudflare Pages（不變） |

---

## 4. 階段性目標

```
Phase 1：Backend MVP（2 週）
  □ Laravel 專案、DB schema 建好
  □ 把現有 games.json 匯入 MySQL（migration script）
  □ 公開 read-only API（GET /games, /games/{id}, /search）
  □ 前端 app.html 改 fetch API（保留現有畫面）

Phase 2：認證 + 投稿（1.5 週）
  □ 使用者註冊 / 登入（email + Google OAuth）
  □ 投稿表單 UI（修正 / 新增 / 翻譯）
  □ POST /contributions 寫入 pending_contributions
  □ 投稿者 dashboard 看自己投稿狀態

Phase 3：管理員審核（1 週）
  □ Filament admin panel 起手
  □ 審核佇列：approve / reject + 留言
  □ Approve → 自動套用到 games / descriptions
  □ Audit log 記錄誰改了什麼

Phase 4：管理員後台 + 統計（1 週）
  □ Dashboard：投稿統計、今日 PV、缺資料 top 排行
  □ 使用者管理、權限設定
  □ 系統設定、外部 API 金鑰管理

Phase 5：搬遷上線（3 天）
  □ Production 部署、DNS 切換
  □ 灰度：dictionary.retrogame.works/v3 並行測試
  □ 確認穩定後切流量
```

---

## 5. 不變的原則

1. **資料不滅**：現有 `data/*/games.json` 仍在 git，作為 source of truth 備份。
2. **前端 URL 不變**：`/app.html#/pce/g/1941` 之類的 hash routing 在 v3 仍可用（後端只負責 API、不接管 routing）。
3. **靜態資產仍走 Cloudflare**：封面 / 截圖 URL 不會變（reuse libretro-thumbnails CDN）。
4. **離線可讀**：bundled offline 版本（app-offline.html）仍維護，方便沒網路時用。
5. **多語**：中文優先，英文 / 日文補充，跟現在一致。

---

## 6. 文件導航

| 檔案 | 內容 |
|---|---|
| `00-Architecture-Overview.md` | （本檔）架構總覽 + 階段規劃 |
| `01-Backend-Stack.md` | Laravel 結構、模組、目錄、套件選型 |
| `02-Database-Schema.md` | MySQL 14 張表 ER 圖 + DDL |
| `03-API-Endpoints.md` | REST API spec（路徑、參數、回應） |
| `04-User-Contributions.md` | 使用者投稿前端 UX + 後端流程 |
| `05-Admin-Moderation.md` | 管理員審核佇列 + 工作流程 |
| `06-Admin-Backoffice.md` | 管理員後台所有功能 |
| `07-Migration-Plan.md` | 從 v2 靜態到 v3 的搬遷步驟 |

---

## 7. 開放問題（需你決定）

1. **前端要重寫嗎？**（A / B / C 上面那三個選項）
2. **要 OAuth 登入嗎？**（Google / Apple / Discord 任選）
3. **匿名能投稿嗎？**（不註冊只填 email + 驗證信，還是強制註冊？）
4. **多語 UI**：管理後台要中文 only 還是中英雙語？
5. **付費層 / 贊助**：以後要不要做？（會影響 user 表設計）
6. **Hosting 預算**：~$10/mo VPS 夠？還是要 fully managed Laravel Vapor（無伺服器）？
