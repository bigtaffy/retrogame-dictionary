# 09 — Migration Plan（v2 → v3 搬遷計劃）

> 從現有靜態 SPA + JSON 搬到 Laravel + MySQL。零停機、可回退。

---

## 1. 高階策略：並行 + 灰度

**不要 big-bang 一次切換**。三個階段：

```
Phase A：v2 不動，並行架 v3 (2 週)
  - dictionary.retrogame.works   → v2 (現狀)
  - api.dictionary.retrogame.works → v3 API（新）
  - dictionary.retrogame.works/v3 → v3 SPA（新前端）
  使用者可選試 /v3，預設仍是 v2

Phase B：v3 灰度 (1 週)
  - 50% 流量轉 v3，DNS A/B
  - 蒐集錯誤 / 效能數據
  - 若有問題 1 click 切回 v2

Phase C：v3 正式取代 v2 (1 天)
  - DNS 全切 v3
  - v2 仍保留 2 週當 fallback (deploy/v2/)
  - 確認穩定後刪 v2
```

---

## 2. 詳細步驟

### Phase A：起新環境

#### 2.1 開 server
- VPS（DO 4 vCPU / 8 GB / 80 GB SSD ~$20/mo）
- Ubuntu 22 LTS
- Laravel Forge 一鍵部署 PHP 8.3 + Nginx + MySQL 8 + Redis 7
- 域名 `api.dictionary.retrogame.works` 指向 server IP
- Cloudflare proxy (橘雲) 開

#### 2.2 開 R2 bucket
- 名 `rgd-uploads` + `rgd-mirrors`
- IAM 限定 Laravel app 寫入
- 自定子域 `cdn.dictionary.retrogame.works` → R2

#### 2.3 Laravel 專案初始化
```bash
laravel new rgd
composer install
cp .env.example .env
php artisan key:generate
# 設 DB / Redis / R2 / OAuth keys
```

安裝 02 doc 提到的所有套件。

#### 2.4 跑 migration（建表）
```bash
php artisan migrate
```
產 14 張主表 + spatie permissions / activity log 子表。

#### 2.5 Seed 資料字典
```bash
php artisan db:seed --class=ConsolesSeeder    # 5 主機
php artisan db:seed --class=GenresSeeder      # 17 主類型 + 30 子類型 (見 08-Genre-Taxonomy)
php artisan db:seed --class=RegionsSeeder     # 6 地區
php artisan db:seed --class=RolesSeeder       # 4 角色 + 權限
```

#### 2.6 匯入現有 JSON
```bash
php artisan rgd:import-json data/games.json --console=pce
php artisan rgd:import-json data/gba/games.json --console=gba
php artisan rgd:import-json data/fc/games.json  --console=fc
php artisan rgd:import-json data/gb/games.json  --console=gb
php artisan rgd:import-json data/gbc/games.json --console=gbc
```

匯入流程（見 ImportJsonCommand）：
1. 讀 JSON
2. 對每筆 game：
   - INSERT into games（基本欄位）
   - INSERT into titles（title_en/zh/jp）
   - INSERT into descriptions（overview_en/zh, comment_en/zh, wiki_extract_en）
   - INSERT into images（cover, screenshots[]）
   - INSERT into videos（youtube[]）
   - INSERT into game_regions（依 regions[] 拆解）
   - INSERT into game_genres（依 08 對照表）
3. UPDATE game.cover_image_id, primary_title_id

#### 2.7 驗證匯入結果
```bash
php artisan rgd:verify-import
```
比對：
- 各主機 game count 跟 JSON 一致
- 100 筆抽樣 detail 的 title / cover / overview 跟原 JSON 完全一致
- FULLTEXT index rebuilt

---

### Phase A.2：API + 前端

#### 2.8 寫 API（公開 read-only）
最小可用集：
- `GET /consoles`
- `GET /games?...` （列表 + 篩選）
- `GET /games/{id}` （詳細）
- `GET /search?q=...`
- `GET /random`

所有 endpoint 上 caching headers + Redis cache。（07-Performance-Caching）

#### 2.9 前端改用 API
方案 A：**只改 fetch logic，UI 不變**
```js
// 舊
const games = await fetch('data/gba/games.json').then(r => r.json());

// 新
const { data: games, meta } = await fetch('https://api.dictionary.retrogame.works/api/v1/games?console=gba').then(r => r.json());
```

第一版前端就這樣，部署到 `dictionary.retrogame.works/v3/`。原 v2 仍在 `/`。

#### 2.10 內部測試
- Admin 們先試 /v3
- 列出 bug list，1 週內修

---

### Phase B：認證 + 投稿

#### 2.11 OAuth + 註冊
- Google client_id 申請
- Laravel Socialite 配 Google driver
- email/password 註冊 + 驗證信流程

#### 2.12 投稿系統
- POST /contributions endpoint
- 上傳到 R2
- 通知 mod（Slack webhook）
- mod 後台佇列頁

#### 2.13 Admin 後台
- 安裝 Filament v3
- 建 Resources：Game / Console / User / Contribution / Genre
- 接 06 doc 那些 dashboard 元件

---

### Phase B.2：灰度

#### 2.14 DNS A/B
Cloudflare Workers 路由：
```js
addEventListener('fetch', e => {
  const userBucket = hashIp(e.request.headers.get('CF-Connecting-IP')) % 100;
  const v3 = userBucket < 50;
  e.respondWith(fetch(v3 ? V3_ORIGIN : V2_ORIGIN, e.request));
});
```

50/50 轉。觀察 7 天：
- error rate（v3 < v2 + 0.1%）
- p95 latency（v3 < 200ms）
- 投訴 / 客訴
- 投稿成功率

#### 2.15 fallback 機制
首頁加小 banner：「想試新版？[切到 v3]」/ 「想回舊版？[切到 v2]」

---

### Phase C：切換正式

#### 2.16 DNS 全 v3
Workers 直接 100% 轉 v3。

#### 2.17 v2 archive
- v2 程式碼放 `archived/v2/` 目錄，不刪
- 保留 `dictionary.retrogame.works/legacy/` 子路徑指向 v2 靜態檔（純歷史備份）
- 2 週後若無問題刪除 legacy

#### 2.18 通知使用者
網站頂部 dismiss-able banner：
> 🎉 v3 已上線！新功能：使用者投稿、即時搜尋、更快載入。
> [了解更多]

---

## 3. 資料同步策略（v2 vs v3 過渡期）

Phase B 期間，v2 跟 v3 可能分歧：
- v2 直接改 JSON，git push
- v3 改 DB

**對策**：v2 進入「凍結」狀態，過渡期不再改資料，所有編輯在 v3 admin 後台做。每天有 cron job 把 v3 DB 變更 export 成 JSON 寫回 git（讓有人 fork v2 的能拿到新資料）。

```bash
php artisan rgd:export-to-json --output=data/
git add data/ && git commit -m "auto: daily export"
git push
```

---

## 4. 回退（rollback）

每階段都要可回退：

| 階段 | 回退方式 | 影響 |
|---|---|---|
| Phase A | 不影響 v2 | 0 |
| Phase B | DNS Worker 切回 100% v2 | 投稿者帳號 / 投稿仍在 v3 DB |
| Phase C | 把 v2 重新部署到 `/`，DNS 切過去 | 新投稿在 v3 DB（孤立 1-2 天等修） |

每個階段切換前 snapshot DB（mysqldump / RDS snapshot）。

---

## 5. 測試清單

### 自動化
- [ ] Pest unit + feature 測試 80% coverage
- [ ] API contract 測試（所有 endpoint 用 spec 跑）
- [ ] DB migration roundtrip（migrate fresh + seed = 同樣結果）
- [ ] Import script 跑完 → 跟 JSON binary diff 99%+ 一致

### 手動 smoke test
- [ ] 5 個主機都載得到
- [ ] 100 random games 詳細頁正常
- [ ] 搜尋「mario」回 ≥ 50 筆
- [ ] 篩 genre / region / format 都正確
- [ ] 圖片 / 截圖 / 影片都載得到
- [ ] 投稿流程跑完（submit → mod 通過 → game 變更）
- [ ] OAuth 登入 / 登出 / 重設密碼

### 效能
- [ ] k6 / wrk：100 concurrent users，95% < 200ms
- [ ] LightHouse 行動 score ≥ 85
- [ ] LCP < 2.5s on 3G simulation

---

## 6. 時程預估

| 階段 | 工作 | 時間 |
|---|---|---|
| A.1 | Server / DB / R2 設置 | 1 天 |
| A.2 | Schema migrate + seed | 1 天 |
| A.3 | Import script + 跑完 | 2 天 |
| A.4 | Read-only API 寫完 | 3 天 |
| A.5 | 前端改 fetch API | 2 天 |
| A.6 | Phase A 內部測試修 bug | 3 天 |
| B.1 | OAuth + 註冊 | 2 天 |
| B.2 | 投稿前後端 | 4 天 |
| B.3 | Admin 後台（Filament） | 5 天 |
| B.4 | Phase B 內測 | 2 天 |
| B.5 | 灰度 50/50 | 7 天 |
| C.1 | DNS 全切 + 監控 | 1 天 |
| **總計** | | **~33 天 / 5 週** |

一個工程師 full-time。如果兼職減半進度，10 週左右。

---

## 7. 風險與緩解

| 風險 | 影響 | 緩解 |
|---|---|---|
| 匯入腳本漏資料 | 部分遊戲缺欄位 | verify-import 抽樣 + diff |
| API 比 JSON 慢 | UX 退步 | 嚴格上 Redis + CDN cache |
| Cloudflare worker bug | 灰度切失敗 | 一鍵停 Worker 直接走原 origin |
| Admin 後台用不慣 | 編輯效率掉 | 雙人並肩 onboard 1 hr |
| 投稿者腳本攻擊 | spam 灌爆 | rate limit + captcha + 聲望 |
| MySQL 資料不可逆刪除 | 操作錯救不回 | 每日 mysqldump 備份 14 天 |

---

## 8. 完成判斷（DoD）

整個 v3 上線完成的標準：

- [ ] v3 在主域名跑滿 7 天 0 critical bug
- [ ] DAU 比 v2 上線時 ≥ 持平
- [ ] 累積至少 50 個註冊使用者
- [ ] 累積至少 100 筆投稿（pending + processed）
- [ ] 至少 30 筆使用者投稿被通過
- [ ] Admin 後台 5 個 mod 操作熟練
- [ ] 文檔 README + API doc + 投稿 Guide 上線

---

## 9. 開放問題

1. **VPS 還是 serverless？** Vapor (Lambda) 對突發流量好但 Cold Start。建議 v3 用 VPS，未來重新評估。
2. **v2 程式碼是否從 git 移除？** 建議**保留** archived/ 內，git history 完整。
3. **要不要做 i18n 多語 UI？** 目前 ZH only。建議 v3 上線後 v3.1 加 EN UI。
4. **CI/CD 用 GitHub Actions / Forge / Envoy？** 推 Forge（一鍵 deploy 簡單）。
5. **Docker 化嗎？** 短期 VPS 直裝即可。Docker 化 v3.1 再做（dev 環境用 Sail 即可）。
