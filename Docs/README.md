# Retro Game Dictionary v3 — Design Docs

> 復古遊戲字典從靜態 SPA 升級成 Server / Client 架構（Laravel + MySQL）的設計文件集。
> **這只是設計文件，沒有任何程式碼或資料變更。** 確認方向後才動工。

---

## 文件導航

| # | 檔案 | 主題 | 何時讀 |
|---|---|---|---|
| 00 | [Architecture-Overview.md](./00-Architecture-Overview.md) | 整體架構、技術選型、階段規劃 | 第一個讀 |
| 01 | [Backend-Stack.md](./01-Backend-Stack.md) | Laravel 結構、套件、目錄、設定 | 後端開工前 |
| 02 | [Database-Schema.md](./02-Database-Schema.md) | MySQL 14 張表 ER 圖 + 完整 DDL | 寫 migration 前 |
| 03 | [API-Endpoints.md](./03-API-Endpoints.md) | REST API 規格、認證、回應格式 | 前後端對接時 |
| 04 | [User-Contributions.md](./04-User-Contributions.md) | 一般使用者投稿功能（UX + 流程） | 規劃 Phase B |
| 05 | [Admin-Moderation.md](./05-Admin-Moderation.md) | 管理員審核佇列工作流程 | 同上 |
| 06 | [Admin-Backoffice.md](./06-Admin-Backoffice.md) | 管理員後台所有功能 | 同上 |
| 07 | [Performance-Caching.md](./07-Performance-Caching.md) | 效能與快取策略（橫切面） | 設計每個 endpoint 都要看 |
| 08 | [Genre-Taxonomy.md](./08-Genre-Taxonomy.md) | 遊戲類型重新分類 + migration 表 | DB seed 前 |
| 09 | [Migration-Plan.md](./09-Migration-Plan.md) | v2 → v3 切換步驟、回退、時程 | 動工前 |

---

## 三大原則

1. **使用者瀏覽不打 server**：CDN + Redis + 前端 cache 三層擋下 90%+ 請求（07-Performance-Caching）
2. **資料不可逆操作要審核**：投稿 / 修改都進 pending 佇列，admin 通過才生效（04 + 05）
3. **零停機搬遷**：v2 / v3 並行 → 灰度 50/50 → 全切 → 隨時可回退（09）

---

## 三大新功能

### 使用者投稿
每個欄位旁的 ✎ → 表單 → pending → admin 審核 → 通過後套用 → 通知投稿者
（→ 04-User-Contributions）

### 管理員審核 + 後台
獨立子網域 `admin.dictionary.retrogame.works` → 佇列、批次操作、smart suggestions、audit log、KPI dashboard
（→ 05-Admin-Moderation + 06-Admin-Backoffice）

### Genre 重新分類
65 個亂值 → 17 主類型 + 30 子類型 + 完整 migration 對照表（97% 自動歸位）
（→ 08-Genre-Taxonomy）

---

## 待你決定的問題（彙整）

各 doc 結尾有「開放問題」一節。最關鍵：

| Q | 推薦選項 | 來源 |
|---|---|---|
| 前端要不要重寫成 Vue？ | **A. 維持 vanilla 過渡 → B. 之後重寫** | 00 |
| OAuth 登入用哪個？ | **Google + Email password** | 04 |
| 匿名能投稿嗎？ | **不行**（須註冊 + email verify） | 04 |
| 後台 UI 框架？ | **Filament v3** | 01, 06 |
| Hosting？ | **VPS（DO/Linode + Forge）** | 09 |
| 上線後保留 v2 多久？ | **2 週 fallback，之後 archive** | 09 |
| 「平台 Platformer」當主類型還是子類型？ | **子類型** | 08 |
| 多 tag 還是單 tag？ | **1 primary + N tags** | 08 |
| 「成人」內容預設要顯示嗎？ | **預設隱藏，user 開啟** | 08 |
| ROM hash（CRC32 / MD5）要存嗎？ | **要**（為了未來對接 RetroArch / Batocera） | 02 |

回答完這些就可以開動。

---

## 階段時程

```
Phase A：Backend MVP        2 週
Phase B：認證 + 投稿        1.5 週
Phase B.2：Admin 後台       1 週
Phase B.3：灰度測試         1 週
Phase C：切換正式           3 天

總計：5 週（一個工程師 full-time）
```

詳見 [09-Migration-Plan.md](./09-Migration-Plan.md)

---

## 不變的東西

- 現有 `data/*/games.json` 仍是 source of truth 備份（git history）
- 前端 hash routing (`#/pce`, `#/gba/g/123`) 不變
- 圖片 URL 不變（continue from libretro-thumbnails）
- 中文優先、英文 / 日文補充

---

## 改動了的東西

| 舊 | 新 |
|---|---|
| 改資料 → 編 JSON → git push | API call，即時生效 |
| 列表 / 詳細頁：fetch 7 MB JSON | 分頁 + cache 友善 API |
| 沒有使用者帳號 | OAuth + email |
| 沒有投稿系統 | pending 佇列 + 審核 |
| 沒有後台 | Filament dashboard |
| 沒有快取 | 三層 cache |
| 沒有 audit log | 全操作可追溯 |
| genre 65 個亂值 | 17 主類型 + 子類型 |

---

## 開發紀律

每加 feature 都檢查：

- [ ] 對應到哪份文件？文件需要更新嗎？
- [ ] 此 endpoint 能 cache 嗎？TTL 多久？
- [ ] 寫入後哪些 cache 要 invalidate？
- [ ] 加了新欄位 / 表嗎？schema 文件更新？
- [ ] 改了 API contract 嗎？前端通知了嗎？
- [ ] 加了測試嗎？

---

## 文件版本

- 撰寫日：2026-04-26
- 文件作者：bigtaffy + Claude（協作）
- 對應 v2 程式碼：commit `2400575` 之後
- 預計動工：v3 設計確認後

每次重大設計變更時，更新對應 doc 的「## 開放問題」區塊，並在這份 README 附加變更日期。
