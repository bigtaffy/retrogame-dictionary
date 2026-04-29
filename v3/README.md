# v3 程式目錄

- **`frontend/`** — 公開站：**Vite + React + TypeScript**；**版面與倉庫根目錄 `app.html` 同一套**（已匯入其 CSS、頂欄主機圖、控制列、網格卡片、詳情、搜尋層、Lightbox 等；樣式在 `frontend/src/styles/v2-app.css`）。
- **`backend/`** — **Laravel** + **Filament** + **MySQL**

**規格**：`Docs/11-v3-Stack-Decisions.md`；API 合約 `Docs/03-API-Endpoints.md`。

上層目錄的 v2 靜態站與 `data/` **沒有改動**，僅在本資料夾新增程式。

## 實際版本（安裝程式拉到的版本）

- Laravel **13**（`composer create-project` 目前預設樣板）
- Filament **4**
- 文件若仍寫「Laravel 11 + Filament 3」，以實作版本為主，行為上持續對齊 `Docs/` 合約

## 本機 MySQL

已預期存在資料庫 **`rgd`**（本機曾用 `root` 無密碼建立；若你環境不同，請改 `backend/.env`）。

## 資料庫與 v2 相容

- 結構依 **`Docs/02-Database-Schema.md`**：主表 **`games`** 與 **`titles`、`descriptions`、`images`、`videos`、`genres`／`game_genres`** 等；**`consoles`** 內建 `pce, gb, gbc, gba, fc` 與 migration 插入之 **`md`（Mega Drive）**。
- 倉庫根目錄 **`data/*.json`（v2 單筆形狀）** 以 **`php artisan rgd:import-v2`** 寫入；**`--console=all`** 可一次匯入六臺主機。單一主機重匯可 **`--truncate`**（僅刪該 `console_id` 之遊戲與關聯）。

```bash
cd v3/backend
php artisan migrate
# 匯入全部主機（耗時與 JSON 體積有關，首次建議在效能足夠的機器執行）
php artisan rgd:import-v2 --console=all --truncate
# 只匯 PCE 時也可：
php artisan rgd:import-v2 --console=pce --truncate
```

- **公開 API**（`data[]` 內單筆＝v2 檔案欄位，含 `region_category`／`regions`／`publisher` 等；**六臺主機**皆用 `?console=` slug）  
  - `GET /api/v1/games?console=gba&page=1&per_page=12&q=關鍵字`  
  - `GET /api/v1/games/{legacy_id}?console=gba`（`legacy_id` 為 v2 的 `id` 欄位）

## 一鍵自測（兩個終端機）

**終端 1 — 後端**

```bash
cd "…專案路徑…/v3/backend"
php artisan serve --host=127.0.0.1 --port=8001
```

若本機 `8000` 沒被佔用，可改 `--port=8000`，並把 `backend/.env` 的 `APP_URL` 與 `frontend/.env` 的 `VITE_API_BASE_URL` 改成同一 port。

- 健康檢查：<http://127.0.0.1:8001/api/v1/health>
- 內建 health：<http://127.0.0.1:8001/up>
- **Filament 後台登入**：<http://127.0.0.1:8001/admin/login>  
  - 帳號欄位請填 **Email**：`admin@local.test`（顯示名稱為 **admin**）  
  - 密碼：`123Qwe`  
  - 若**登不進**：在 `v3/backend` 執行  
    `php artisan db:seed --class=AdminUserSeeder`（會建立 `admin@local.test` 或把密碼重設成 `123Qwe`）  
- **後台內容（v3 已接）**  
  - 儀表板：各主機**遊戲筆數**統計  
  - **主機**（`Consoles`）：只讀列表與小修改（中英文名、排序、圖示 URL）；**不可**新增/刪除主機列  
  - **遊戲**（`Games`）：依主機篩選的列表、搜尋/編輯、子表**多語標題**與**簡介/短評**、單筆的 `external_links` JSON

**終端 2 — 前端**

```bash
cd "…專案路徑…/v3/frontend"
npm install
npm run dev
```

瀏覽器開 Vite 印出的位址（通常 `http://127.0.0.1:5173`）。**hash 與 v2 靜態站 `app.html` 相同**（例如 `/#/pce`、`#/md/g/...`、`#/discover`）。**所有主機的列表／探索／詳情**皆透過 **`VITE_API_BASE_URL` 的 Laravel API** 取得 JSON（須已 `rgd:import-v2` 匯入對應主機）。後端可選保留 `GET /v2data/...` 作開發用讀靜態檔，**非遊戲清單之必須路徑**。

## 建置產物檢查

```bash
cd v3/frontend && npm run build
```

`frontend/dist/` 可部署到靜態主機；後端仍跑在 VPS。
