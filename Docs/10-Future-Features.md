# 10 — Future Features 評估

> 兩個延伸功能：(1) 網頁內建模擬器、(2) ESP32 掌機聯動。
> 都以「v3 Server/Client + Cloudflare Pages」為前提評估。

---

## 1. 🕹 網頁直接玩遊戲（In-Browser Emulator）

### 1.1 結論先講

**可行度：🟢 高**（卡帶系統 NES/GB/GBC/GBA/MD）/ **🟡 中**（CD 系統 PCE-CD/Mega-CD，有 BIOS 法律問題）

**架構衝擊：幾乎沒有** — 純前端 WASM，不需後端、不增加 server loading、Cloudflare Pages 完美承擔。

**主要挑戰：ROM 法律問題** — 我們**不能**散佈 ROM。只能讓使用者上傳自己合法擁有的 ROM 檔。

---

### 1.2 技術可行性

#### 現成方案：EmulatorJS

[EmulatorJS](https://emulatorjs.org) 是開源的 web 模擬器框架，把 libretro 核心編譯成 WASM 在瀏覽器跑。
**已支援我們所有 6 個主機**：

| 主機 | libretro 核心 | EmulatorJS 支援度 |
|---|---|---|
| PCE / TG-16 | mednafen_pce_fast | ✅ |
| PCE-CD / Mega-CD | 同上（要 BIOS） | 🟡 需 BIOS |
| GB / GBC | mgba / gambatte | ✅ |
| GBA | mgba | ✅ |
| FC / NES | fceumm / nestopia | ✅ |
| MD / Genesis | genesis_plus_gx | ✅ |

整合一個 EmulatorJS：

```html
<div id="game-frame"></div>
<script src="https://cdn.emulatorjs.org/4.0.13/data/loader.js"></script>
<script>
  EJS_player = '#game-frame';
  EJS_core = 'genesis_plus_gx';   // 對應主機自動選
  EJS_gameUrl = userUploadedRomUrl;
  EJS_pathtodata = 'https://cdn.emulatorjs.org/4.0.13/data/';
</script>
```

#### 性能

| 主機 | 桌機（Chrome M1） | 手機（iPhone 12+） |
|---|---|---|
| NES / GB / GBC | **60 fps 全程** | 60 fps |
| PCE / GBA | 60 fps | 多數 60 fps，特定場景掉 |
| MD | 60 fps | 60 fps |
| MD-CD / PCE-CD | 50-60 fps | 30-50 fps（CD ROM 大） |

#### 存檔

- Save State：存 IndexedDB（user 瀏覽器內，跨 session）
- 雲端 sync（v3.x 後加）：透過 API 上傳到後端，登入後跨裝置

#### ROM 取得

我們**絕對不**散佈 ROM。但可：
- **使用者上傳**：file picker / drag-drop。ROM 留在瀏覽器 memory，**不送到 server**
- **Homebrew / 公領域 ROM**：少數遊戲（如 Daedalian Opus、Mega CD homebrew）合法可散佈 — 我們可以選擇性提供
- **Aftermarket / Demo**：libretro-thumbnails 已標出哪些是 aftermarket，可標示「此遊戲提供合法可玩 demo」

#### BIOS 法律問題（CD 系統）

Mega CD / PCE-CD / GBA（部分核心）需要原機 BIOS 才能跑。BIOS 同樣**有版權**，我們不能散佈。

選項：
- 跳過 CD 系統（v1 不做）
- 引導使用者自備 BIOS（教學頁，類似 RetroArch wiki 那套）
- 用 freebios（GBA 的自由替代品；其他主機沒有）

---

### 1.3 跟 v3 架構搭配

```
[Cloudflare Pages]
    ├── /app.html (SPA — 字典本體，不變)
    ├── /play/{console}/{slug}.html  ← 新頁面
    │   └── 使用者拖 ROM 進來 → EmulatorJS 跑
    └── /static/emulatorjs/* (CDN cache)
```

**完全在瀏覽器**跑。後端 0 額外 loading。

API 加一個 endpoint：
```
GET /api/v1/games/{id}/play-info
```
回 EmulatorJS 配置（要哪個 core、控制鍵 mapping、預設 BIOS path）—— 可 cache 1 day。

#### 整合到 detail page

每張 detail 卡上加按鈕：
```
[ ▶ 在瀏覽器試玩 ]   [ 上傳 ROM ]
```

點 → 開新頁 / iframe → 拖 ROM 檔進去 → 開玩。

---

### 1.4 預估工程量

| 階段 | 工作 | 時間 |
|---|---|---|
| Phase E.1 | 整合 EmulatorJS、寫 /play 頁面骨架 | 1 天 |
| Phase E.2 | ROM 上傳 + Save State + 控制設定 UI | 2 天 |
| Phase E.3 | 各主機 core 對應 + 性能測試 | 1 天 |
| Phase E.4 | 控制鍵自訂、手機觸控按鈕 | 1 天 |
| Phase E.5 | 雲端 save state（v3.1） | 1 天 |
| **總計** | | **~6 天** |

---

### 1.5 法律免責

頁面要明確標示：
> ⚠️ ROM 由使用者自行提供。我們不散佈、不存儲任何 ROM 檔。請確保你擁有合法版本（你買的卡帶 / CD）。
> Save State 只存在你的瀏覽器，不上傳。

跟 RetroArch 同樣立場。多數使用者已習慣。

---

### 1.6 開放問題

1. **要不要做？** 確定要做的話 v3 第二期排進來
2. **要不要支援雲端 Save State？** 拉高 server loading（雖然不大）
3. **手機要不要做觸控按鈕？** 必要 — 不然手機沒 keyboard 沒得玩
4. **要不要 social feature**（公開 high score）？ 看做不做廣度
5. **CD 系統怎麼解？** v1 跳過、文件指引玩家自己處理 BIOS
6. **EmulatorJS 還是自架？** 用他們 CDN 最簡單；若要客製 UI 自架版本（fork）也行

---

---

## 2. 🎮 ESP32 掌機聯動

### 2.1 結論先講

**可行度：🟢 高**（API 字典查詢） / **🟡 中**（深度雙向同步）

**價值：niche 但獨特** — 你已經在做 ESP32 掌機，字典可以變成它的「線上資料庫 + 雲端 save 中心」。

**架構衝擊：v3 後端要新增 ESP32 專屬 endpoints**（極簡 schema、低頻寬），但本來就要寫 API、增量小。

---

### 2.2 ESP32 環境約束

| 項目 | 限制 |
|---|---|
| RAM | 320KB（ESP32-S3 Octal 8MB PSRAM） |
| Flash | 4-32MB 內建 + microSD（GB/SD 等級） |
| HTTPS 速度 | 100-300KB/s（WiFi 802.11n） |
| 螢幕 | 240×240 / 320×240 / 480×320 ILI9341/ST7789 |
| 顏色 | 16-bit RGB（65,536 色，能讀我們所有 cover） |
| 算力 | NES/GB/GBC 60fps 沒問題；GBA 部分掉幀；MD 看核心 |
| 顆粒度 | 圖片 200KB 內、文字 1KB 內 |

---

### 2.3 三種聯動模式（從淺到深）

#### 模式 A：ESP32 線上查詢字典（最低門檻）

ESP32 透過 WiFi 打我們的 API，**用我們字典當成它的 ROM 資料庫**。

使用情境：
1. ESP32 開機 → 掃描 microSD 上的 ROM 檔（filename）
2. 對每個 ROM 算 hash（CRC32 一次）
3. 查 `GET /api/v1/games/lookup?hash=XXXXXX&console=md`
4. API 回 `{ title_zh, cover, year, genre, overview_zh }`
5. ESP32 LCD 顯示中文遊戲名 + 縮圖封面 + 簡介

需要的 API endpoint（v3 加）：
```
GET /api/v1/games/lookup?hash={crc32}&console={slug}
GET /api/v1/games/lookup?no_intro_name={n}&console={slug}
```

回 thin payload（< 2KB / 遊戲）：
```json
{
  "title_zh": "獸王記",
  "title_jp": "獣王記",
  "cover_thumb": "https://cdn.../md/juuouki_120.webp",  // 120px 寬
  "year": "1988",
  "genre": "動作",
  "overview_zh": "..."
}
```

**價值**：ESP32 上的 microSD 一插，遊戲名直接從英文檔名變成中文標題 + 顯示封面。**用戶體驗大升級**。

工程量：v3 API 多兩個 endpoint，ESP32 firmware 寫 HTTP client + JSON parser + JPEG 解碼，~2 天。

#### 模式 B：個人化收藏 / 進度同步

使用者在字典網站登入後，新功能：
- **「我的 ROM 庫」**：標註自己擁有哪些 ROM（純文字 list，不存 ROM 本身）
- **「我的 Save」**：ESP32 把 save state 上傳到伺服器
- **「我的進度」**：ESP32 報「我玩了 X 小時、達到 Y 關」

ESP32 firmware：
- 設定頁輸入 user PAT（伺服器產的長 token）
- 啟動時對 `/api/v1/me/library` POST 自己看到的 ROM
- 玩遊戲時定期 POST save state（每 5 分鐘 / 關卡結束）

API（v3 加）：
```
GET  /api/v1/me/library
POST /api/v1/me/library/{game_id}/save        ← 上傳 save state
GET  /api/v1/me/library/{game_id}/save        ← 取回（換機可繼續玩）
POST /api/v1/me/library/{game_id}/playtime    ← 玩了多久
```

Cloudflare R2 存 save state（每筆 < 1MB）。

**價值**：換機（從 ESP32 → 桌機 EmulatorJS）也能繼續玩。

工程量：~3 天（後端 + ESP32 firmware）。

#### 模式 C：ESP32 + 字典深度整合（雙向社群）

使用者多了，可以做：
- **公開 high score**：ESP32 報榜 → 字典上每款遊戲頁顯示 top 10
- **社群評論**：在 ESP32 玩完 → 一鍵寫評論到字典（ESP32 鍵盤打字超痛，所以更可能是預設 emoji 評分）
- **推薦**：「玩過《獸王記》的玩家也喜歡《Streets of Rage》」基於 ESP32 telemetry

工程量：大幅增加（~2 週），但這是長期路線。

---

### 2.4 跟 Cloudflare Pages 搭配

ESP32 全部走我們 v3 backend（VPS + MySQL + Redis）。Cloudflare 在前面 cache 公開 endpoints。

特別注意：
- ESP32 對 TLS 不友善（只支援部分 cipher suites）→ 確保我們 server 支援 ESP32 常見 chipher（Cloudflare 預設 OK）
- 圖片走 R2（120px webp），ESP32 用單張 JPEG 解碼（mjpeg lib）

---

### 2.5 預估工程量

| 模式 | 工作 | 時間 |
|---|---|---|
| **A** | API endpoint + ESP32 firmware HTTP/JSON/image client | 2 天 |
| **B** | + 帳號 token + save state R2 + ESP32 sync | 3 天 |
| **C** | + 公開 high score + 評論 + 推薦演算法 | 2 週 |

**建議：先做 A**（馬上有價值、工程小），看反應再決定 B / C。

---

### 2.6 開放問題

1. **你已經有的 ESP32 板子是哪款？** 不同 board 的 RAM / 螢幕差很多
2. **你想用哪個 emulator base？**（odroid-go 系統 / 自寫）
3. **ROM 你打算怎麼放？**（microSD 自備、還是要做 OTA 從伺服器拉？）
4. **要不要做硬體控制 of 字典網站？**（ESP32 ←→ 字典桌機版透過 BLE 把 ESP32 當搖桿？）
5. **公開資料 vs 私人資料分界？**（save state 是私人、高分是公開）

---

---

## 3. 兩個功能的優先順序建議

| 優先 | 功能 | 理由 |
|---|---|---|
| ⭐⭐⭐ | **網頁模擬器（模式 1.E.1）** | 純前端、Cloudflare 完美承擔、馬上能玩 |
| ⭐⭐ | **ESP32 模式 A（線上查詢字典）** | 工程小、立即把 ESP32 體驗從英文升級到中文 |
| ⭐ | ESP32 模式 B（save state 雲端） | 要先有 user 帳號系統 |
| ⏸ | ESP32 模式 C（社群） | 要先有大量 ESP32 user |
| ⏸ | 網頁模擬器 + 雲端 Save State | 跟 ESP32 模式 B 共用 backend，一起做 |

---

## 4. 對 v3 後端設計的影響

主要 schema 加：

```sql
-- For ROM hash lookup
ALTER TABLE games ADD COLUMN rom_crc32 CHAR(8);
ALTER TABLE games ADD COLUMN rom_md5 CHAR(32);
ALTER TABLE games ADD COLUMN rom_sha1 CHAR(40);
CREATE INDEX idx_games_crc32 ON games(rom_crc32);

-- For save state cloud sync
CREATE TABLE save_states (
  id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id      BIGINT UNSIGNED NOT NULL,
  game_id      BIGINT UNSIGNED NOT NULL,
  source       ENUM('web', 'esp32') NOT NULL,
  storage_path VARCHAR(512) NOT NULL,    -- R2 path
  size_bytes   INT UNSIGNED,
  game_minutes INT UNSIGNED,
  created_at   TIMESTAMP NULL,
  UNIQUE KEY uq_user_game_source (user_id, game_id, source)
);

-- For ESP32 user library (which ROMs user has)
CREATE TABLE user_rom_library (
  user_id      BIGINT UNSIGNED NOT NULL,
  game_id      BIGINT UNSIGNED NOT NULL,
  rom_crc32    CHAR(8),
  added_at     TIMESTAMP NULL,
  last_played  TIMESTAMP NULL,
  total_minutes INT UNSIGNED DEFAULT 0,
  PRIMARY KEY (user_id, game_id)
);
```

API endpoints 加：
- `GET /api/v1/games/lookup` — by hash / no_intro_name
- `GET /api/v1/me/library`
- `POST /api/v1/me/library/{id}/save`

更新 `02-Database-Schema.md` + `03-API-Endpoints.md` 對應段落（v3 動工時記得加）。

---

## 5. 版本路線

```
v3.0 — 後端 + 投稿 + Admin（基礎）
v3.1 — 多語 UI（EN/JP）
v3.2 — 網頁模擬器（mode 1.E.1）
v3.3 — ESP32 線上查詢（mode 2.A）
v4.0 — 雲端 save state 雙向（網頁 + ESP32）
v4.x — 社群功能（mode 2.C）
```
