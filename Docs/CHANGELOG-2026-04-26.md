# 2026-04-26 工作日誌

> 復古遊戲字典 — 一日進度紀錄

---

## 今日完成

### 🎮 1. 第六個主機：Mega Drive 上線
- 新增 `data/md/games.json` — **1,707 款** Mega Drive + Mega CD 遊戲
- 來源：
  - ZH Wikipedia「Mega Drive 遊戲列表」554 款（**100% 中文譯名**、發售日、廠商、型號、售價）
  - libretro-thumbnails 補 853 款西方獨佔卡帶 + 300 款 Mega CD
- 配對：1,407 張封面 + 1,305 套截圖
- SPA 加新 console pill（占位 neon icon）
- commit: `9c34bf7`

### 🏷️ 2. Genre 分類大整理（65 → 16 + 子類型）
- 之前的 65 個亂值（動作 / 動作 (ACT) / 動作 Action / 清版動作 / ...）整併成乾淨的 17 主類型 + 30+ 子類型
- 1,185 款獲得明確的子類型 tag（動作平台、彈幕射擊、戰略角扮等）
- 下拉選單從 65 項變 16 項（97% 自動歸位，3% 真未分類）
- commit: `0b7c275`

### 📝 3. v3 後端架構設計文件（10 份）
寫在 `Docs/` 下，純設計、零程式碼：
- `00-Architecture-Overview` — Laravel + MySQL + 5-phase plan
- `01-Backend-Stack` — 套件選型、結構、安全
- `02-Database-Schema` — 14 張表 ER + DDL
- `03-API-Endpoints` — REST spec + 認證
- `04-User-Contributions` — 投稿系統 UX + 後端流程
- `05-Admin-Moderation` — 審核佇列 + 工作流
- `06-Admin-Backoffice` — 管理員後台所有功能
- `07-Performance-Caching` — 三層快取（CDN + Redis + 前端）
- `08-Genre-Taxonomy` — Genre 重設計 + migration
- `09-Migration-Plan` — v2→v3 灰度搬遷
- README — 導航 + 待你決定的 10 個關鍵問題
- commit: `c8355ef`

### 🔤 4. 字級調整功能
- 預設字級加大 12%（給老花用戶）
- 頂部加 `Aa` 切換鈕（4 級：M / **L 預設** / XL / 2XL）
- 設定存 localStorage
- commit: `60e1e0f`、`66a17d2`（icon 修歪）

### 🎨 5. UI 細節打磨
- View toggle 三個圖示重畫成語意明確：**口** 大圖 / **‖** 純封面 / **呂** 列表 — `7709cc6`、`58b263b`
- 篩選下拉全部中文化（所有規格 / 所有類型 / 所有地區、必買 / 值得試 / 勸退...）— `98642f7`
- 篩選 UI/state 同步保險（切回「所有類型」現在保證 100% 還原）— `98642f7`
- 列表頁滑到中間點遊戲 → 詳細頁從**頂端**開始；按 BACK 回列表 → **滑回離開時的位置** — `129da99`

### 🎮 6. TheGamesDB 抓英文簡介
- 整合 TGDB v1 API（你 5,800 款配額、實際用約 200 calls）
- **GB**: +478 overview_en、release_date、players、age_rating
- **GBC**: +500 overview_en、同上
- **GBA**: +1,372 overview_en（待中譯）
- **FC**: +103 overview_en（NES 西方名跟 FC 日文配對差，覆蓋偏低）
- **MD**: 0（platform ID 推測有誤，待修）
- commit: `5ab7c15` + `0b0da8a`

### 🌏 7. AI 中譯（978 段 GB/GBC）
- 8 個 sub-agent 並行翻譯 GB 478 + GBC 500 = 978 段英文簡介為繁中
- 標註來源 `overview_zh_source: 'thegamesdb_ai'`
- commit: `0b0da8a`

---

## 📊 6 主機現況（截至 2026-04-26）

| 主機 | 遊戲數 | overview_en | overview_zh | 圖片 | 截圖 | 主要來源 |
|---|---:|---:|---:|---:|---:|---|
| **PCE** | 727 | 727 (100%) | **727 (100%)** | 727 (libretro / weserv) | 100% | PCE Bible + libretro |
| **GB** | 803 | 478 (60%) | **478 (60%)** | 99% | 100% | libretro + ZH wiki + TGDB→AI |
| **GBC** | 776 | 500 (64%) | **500 (64%)** | 99% | 99% | libretro + EN wiki + TGDB→AI |
| **GBA** | 2,745 | **1,372 (50%)** ✨新 | 0 | 99% | 99% | libretro + 維基 + TGDB |
| **FC** | 1,242 | 103 (8%) | 0 | 28% | 28% | famicom.tw + libretro |
| **MD** | 1,707 | 0 | **554 (32%)** | 82% | 76% | ZH wiki MD list（中文 100%） + libretro |
| **總計** | **8,000** | **3,180 (40%)** | **2,259 (28%)** | | | |

---

## 🚧 待辦清單（按優先序）

### A. GBA 1,372 段中譯 ⭐ 最有 ROI
- 14 個 sub-agent 並行翻譯
- 預估時間：30 分鐘（之前 8 agent 跑 GB+GBC 978 段花類似時間）
- 上次 spawn 時撞到 API socket 錯誤，需重新跑
- 完成後 GBA 會從 0 → 1,372 中文簡介

### B. MD 的 TGDB platform ID 修正
- 目前 0 hits，platform IDs `[36, 19]` 可能錯
- 解法：腳本加 `--list-platforms` 模式找出 TGDB 上 Mega Drive 的真實 ID（可能是 18 / 36 / 其他）
- 修正後 user 重跑腳本，預期能補 1,000+ MD 描述
- **MD 已有 554 中文（從 ZH wiki）**，這步只是給西方獨佔 + Mega CD 補資料

### C. FC TGDB 重新配對
- 目前只配到 103/1,242（8%）
- 原因：FC 用日文羅馬名（如 Akumajou Dracula），TGDB NES 用西方名（Castlevania）
- 解法：重新配對 — 用 title_jp（平假名 / 片假名）做 katakana → romaji 比對 NES 「(Japan)」標籤的 TGDB 條目
- 預期能拉高到 ~30%

### D. MD 西方獨佔 + Mega CD 中譯（B 完成後）
- 1,153 款還沒中文
- 跟 A 同模式：sub-agent 翻譯

### E. FC 中譯（C 完成後）
- 估計 ~300 款能拿到 overview_en，再翻成中文

### F. v3 架構動工（你決定的時機）
- `Docs/README.md` 那 10 個開放問題回答後就可開始
- Phase A: Backend MVP 2 週

---

## 🎯 最近 3 commits

```
0b0da8a  feat: GB/GBC zh translations + GBA/FC EN overviews from TheGamesDB
5ab7c15  feat(gb,gbc): patch TheGamesDB English overviews + release dates
9c34bf7  feat(md): add Mega Drive (incl. Mega CD) — 1707 games
```

線上：[https://retrogame-dict.pages.dev/app.html](https://retrogame-dict.pages.dev/app.html)
