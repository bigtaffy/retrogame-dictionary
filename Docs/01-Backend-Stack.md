# 01 — Backend Stack（Laravel）

> Laravel 11 後端結構、套件選型、目錄、命名規範。

---

## 1. 環境

| 項目 | 版本 |
|---|---|
| PHP | 8.3 (FPM) |
| Laravel | 11.x（最新 LTS） |
| MySQL | 8.0+，Server collation = `utf8mb4_0900_ai_ci` |
| Redis | 7.x |
| Composer | 2.x |
| Node | 20.x（前端 build；若改 Vue / Inertia） |

`.env` 主要設定：

```ini
APP_NAME="Retro Game Dictionary"
APP_URL=https://api.dictionary.retrogame.works
APP_LOCALE=zh_TW
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=rgd
DB_USERNAME=rgd_app
DB_PASSWORD=...

REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# OAuth
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...

# 外部 API
SCREENSCRAPER_DEVID=...
SCREENSCRAPER_DEVPASSWORD=...

# 物件儲存
FILESYSTEM_DISK=r2
R2_ENDPOINT=...
R2_BUCKET=rgd-uploads
```

---

## 2. 模組劃分（Domain-Driven）

採用 Laravel 預設 `app/` 結構，但功能上分四個 bounded context：

```
app/
├── Domain/                ← 商業邏輯
│   ├── Catalog/           ← 遊戲資料（games / titles / consoles / images）
│   │   ├── Models/Game.php
│   │   ├── Models/Title.php
│   │   ├── Services/GameService.php
│   │   └── Repositories/GameRepository.php
│   ├── Contribution/      ← 使用者投稿
│   │   ├── Models/Contribution.php
│   │   ├── Services/SubmissionService.php
│   │   └── States/Pending.php  (使用 spatie/laravel-model-states)
│   ├── Moderation/        ← 審核
│   │   ├── Services/ModerationService.php
│   │   └── Actions/ApproveContribution.php
│   └── Identity/          ← 使用者 / 權限
│       ├── Models/User.php
│       ├── Models/Role.php
│       └── Policies/
├── Http/
│   ├── Controllers/Api/V1/
│   │   ├── GameController.php
│   │   ├── SearchController.php
│   │   ├── ContributionController.php
│   │   └── Admin/ModerationController.php
│   ├── Resources/         ← API JSON 序列化
│   │   ├── GameResource.php
│   │   ├── GameSummaryResource.php
│   │   └── ContributionResource.php
│   ├── Requests/          ← validation
│   └── Middleware/
├── Console/Commands/      ← Artisan 指令
│   ├── ImportGamesFromJson.php
│   ├── SyncWithLibretro.php
│   └── ScrapeWikipedia.php
├── Jobs/                  ← Queue jobs
│   ├── ProcessContribution.php
│   ├── DownloadCoverImage.php
│   └── TranslateDescriptionWithAI.php
└── Providers/
```

---

## 3. 重要套件

### 必裝
| Package | 用途 |
|---|---|
| `laravel/sanctum` | API token + SPA cookie auth |
| `laravel/socialite` | OAuth（Google / Discord） |
| `spatie/laravel-permission` | RBAC roles + permissions |
| `spatie/laravel-medialibrary` | 上傳圖片（投稿附圖、cover、screenshots） |
| `spatie/laravel-activitylog` | Audit log（誰改了什麼） |
| `spatie/laravel-model-states` | 投稿 state machine（pending → approved / rejected） |
| `spatie/laravel-query-builder` | Listing API：filter / sort / include 都從 query string 解析 |
| `darkaonline/l5-swagger` | OpenAPI auto-doc |
| `laravel/horizon` | Queue dashboard（給 admin 看） |
| `laravel/scout` + `meilisearch/meilisearch-php` | 全文搜尋（中英日多語） |

### 開發期
| Package | 用途 |
|---|---|
| `laravel/telescope` | request / query / job 即時 log（dev only） |
| `larastan/larastan` | static analysis |
| `pestphp/pest` | 測試（比 PHPUnit 簡潔） |
| `nunomaduro/collision` | 漂亮的錯誤訊息 |

### 後台 UI（任選一）
| Package | 用途 |
|---|---|
| **Filament v3** | 推薦。元件 rich、客製化容易、TALL stack |
| Laravel Nova | 官方付費（$199/site） |
| 自寫 Inertia + Vue | 最彈性但工程量大 |

**推薦 Filament**：開箱即用，1 週就可以做出像樣的後台。

---

## 4. API 風格

### 版本化
- 所有 endpoint 在 `/api/v1/` 前綴下
- 未來 v2 可平行存在（route group）

### 認證
- **Public read endpoints**：免認證（GET /games, /search）
- **使用者寫入**：Bearer token via Sanctum（POST /contributions）
- **Admin endpoints**：Bearer token + admin role middleware

### Request / Response 格式
```http
GET /api/v1/games?console=gba&genre=動作平台&page=2&per_page=24
Accept: application/json

200 OK
Content-Type: application/json

{
  "data": [
    { "id": 1234, "title_zh": "超級瑪利歐進化", ... },
    ...
  ],
  "meta": {
    "page": 2,
    "per_page": 24,
    "total": 2745,
    "last_page": 115
  },
  "links": {
    "self": "...",
    "next": "...",
    "prev": "..."
  }
}
```

### 錯誤格式（一致）
```json
{
  "error": {
    "code": "validation_failed",
    "message": "提供的資料有誤",
    "details": {
      "title_zh": ["不能為空"]
    }
  }
}
```

詳細 endpoint 列表 → 看 `03-API-Endpoints.md`

---

## 5. 設定 / Feature Flags

`config/rgd.php`：

```php
return [
    'features' => [
        'contributions_enabled' => env('FEATURE_CONTRIBUTIONS', true),
        'oauth_google'         => env('FEATURE_OAUTH_GOOGLE', true),
        'ai_translation'       => env('FEATURE_AI_TRANSLATION', false),
        'screenscraper_sync'   => env('FEATURE_SS_SYNC', false),
    ],
    'limits' => [
        'contributions_per_user_per_day' => 20,
        'max_image_upload_mb'            => 5,
    ],
    'consoles' => ['pce', 'gba', 'fc', 'gb', 'gbc'],
];
```

---

## 6. Queue / Schedule

### 用 queue 處理的工作
- 投稿提交時：跑驗證 + 通知 moderator
- Moderator approve：把資料寫進正式表 + 重建快取
- 圖片上傳：產 thumbnail（240px / 480px）+ 上 R2
- 外部 API 同步（ScreenScraper / Wiki）：定期 batch job

### 排程（`app/Console/Kernel.php`）
```php
$schedule->command('cache:reload-popular-games')->hourly();
$schedule->command('rgd:cleanup-rejected-contributions')->daily();
$schedule->command('rgd:sync-libretro-thumbnails')->weekly();
$schedule->command('horizon:snapshot')->everyFiveMinutes();
```

---

## 7. 安全

| 項目 | 措施 |
|---|---|
| Rate limit | API 60/min（auth），20/min（guest） |
| CSRF | 後台 Web routes 開、API routes 關（用 token） |
| 圖片上傳 | 驗 MIME + 檔頭、限大小、轉檔（去除 EXIF） |
| XSS | 前端 escape、後端 OutputResource 不含 raw HTML |
| SQL Injection | Eloquent / Query Builder（不寫 raw SQL） |
| 投稿濫用 | hCaptcha + per-user daily quota + IP 黑名單 |
| 密碼 | bcrypt + min 10 chars、登入失敗 5 次鎖定 30 min |
| 第三方 API key | `.env` only、`config:cache` 後不洩漏 |

---

## 8. 監控

- **Laravel Telescope**：開發 only
- **Laravel Horizon**：production 跑 queue 看儀表板
- **Sentry / Bugsnag**：錯誤蒐集
- **Cloudflare Analytics**：前端 PV
- **DB 慢查詢**：`mysqld --slow-query-log` + 每週 review

---

## 9. 測試策略

| 層 | 工具 | 例子 |
|---|---|---|
| Unit | Pest | `GameRepositoryTest::test_find_by_no_intro_name()` |
| Feature | Pest + RefreshDatabase | `ContributionApiTest::test_submit_creates_pending_record()` |
| Browser | Laravel Dusk *(可選)* | end-to-end 投稿流程 |
| Performance | k6 / wrk | API p95 < 100ms |

CI 跑：`vendor/bin/pest --parallel --coverage --min=70`

---

## 10. CI/CD

GitHub Actions：

1. `push` → `main`：跑 lint + tests
2. `push` → `release/*`：deploy 到 staging
3. `tag` → 部署 production（手動 approve）

部署用 Laravel Envoy 或 Forge 一鍵 deploy script，包含：
```
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan queue:restart
php artisan horizon:terminate
```
