# 02 — Database Schema（MySQL 8）

> 14 張主要表 + 索引 + 關聯。utf8mb4 全表，FULLTEXT 用於搜尋。

---

## 1. ER 圖（精簡）

```
                                                        ┌──────────┐
                                                        │  users   │
                                                        └──────────┘
                                                              │ 1
                                                              │
                  ┌───────────────────────┬───────────────────┴────────────┐
                  │                       │                                │
              N   ▼                   N   ▼                                ▼  N
        ┌──────────────┐        ┌─────────────────┐               ┌──────────────────┐
        │ contributions │       │  audit_logs     │               │  moderator_notes │
        └──────────────┘        └─────────────────┘               └──────────────────┘
                │ 1
                │ payload references
                ▼
        ┌──────────────────────────────────────────────────────────┐
        │                      games                                │
        │ ┌─────────┐  ┌──────────┐  ┌───────────┐  ┌─────────────┐│
        │ │ titles  │  │descriptions│  │ images   │  │   videos    ││
        │ └─────────┘  └──────────┘  └───────────┘  └─────────────┘│
        │      N             N             N              N         │
        │   ┌──┴──────────────┴─────────────┴──────────────┴────┐   │
        │   │                  game_id (FK)                      │   │
        │   └────────────────────────────────────────────────────┘   │
        └──────────────────────────────────────────────────────────┘
                                  │ N
                                  │
                                  ▼ 1
                            ┌──────────┐
                            │ consoles │
                            └──────────┘
```

---

## 2. 主表清單

| # | 表名 | 用途 |
|---|---|---|
| 1 | `consoles` | 主機（PCE/GBA/FC/GB/GBC） |
| 2 | `games` | 遊戲核心資料 |
| 3 | `titles` | 多語標題（zh / en / jp） |
| 4 | `descriptions` | 多語簡介 / 評論（按來源分） |
| 5 | `images` | 封面 / 截圖 / marquee / wheel |
| 6 | `videos` | YouTube 影片 |
| 7 | `genres` | 類型字典表 |
| 8 | `game_genres` | M:N 樞紐 |
| 9 | `regions` | 地區字典（J/U/E/K/A/W） |
| 10 | `game_regions` | M:N 樞紐 + 各區發行日 |
| 11 | `users` | 使用者 |
| 12 | `roles` / `permissions` / `model_has_roles` | RBAC（spatie/laravel-permission 套件建表） |
| 13 | `contributions` | 投稿主表 |
| 14 | `contribution_attachments` | 投稿附圖 |
| 15 | `audit_logs` | 操作紀錄（spatie/laravel-activitylog） |

---

## 3. DDL

### 3.1 `consoles`
```sql
CREATE TABLE consoles (
  id          TINYINT UNSIGNED PRIMARY KEY,
  slug        VARCHAR(8)  NOT NULL UNIQUE,         -- 'pce','gba','fc','gb','gbc'
  name_en     VARCHAR(64) NOT NULL,
  name_zh     VARCHAR(64) NOT NULL,
  name_jp     VARCHAR(64) NOT NULL,
  manufacturer VARCHAR(64),                        -- 'NEC','Nintendo'
  release_year YEAR,
  icon_url    VARCHAR(255),                        -- /icons/pce.png
  sort_order  TINYINT UNSIGNED DEFAULT 0,
  game_count_cached INT UNSIGNED DEFAULT 0,        -- denormalized counter
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO consoles (id, slug, name_en, name_zh, name_jp, manufacturer, release_year, sort_order) VALUES
  (1, 'pce', 'PC Engine',         'PC Engine',     'PCエンジン',          'NEC',      1987, 10),
  (2, 'gb',  'Game Boy',          '掌機',           'ゲームボーイ',          'Nintendo', 1989, 20),
  (3, 'gbc', 'Game Boy Color',    '彩色掌機',        'ゲームボーイカラー',     'Nintendo', 1998, 30),
  (4, 'gba', 'Game Boy Advance',  '掌機進化',        'ゲームボーイアドバンス',   'Nintendo', 2001, 40),
  (5, 'fc',  'Famicom',           '紅白機',          'ファミコン',            'Nintendo', 1983, 50);
```

### 3.2 `games`
```sql
CREATE TABLE games (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  console_id      TINYINT UNSIGNED NOT NULL,
  slug            VARCHAR(120) NOT NULL,            -- 例：'1941_counter_attack'
  no_intro_name   VARCHAR(255),                     -- libretro 標準命名
  letter          CHAR(1),                          -- A-Z 或 '#'
  primary_title_id BIGINT UNSIGNED,                 -- → titles.id
  maker           VARCHAR(128),                     -- developer
  publisher       VARCHAR(128),
  release_year    YEAR,
  release_date_jp DATE,
  release_date_na DATE,
  release_date_eu DATE,
  format_category VARCHAR(32),                      -- 'HuCard','CD-ROM','GBA','Cart','Disk'...
  rating          ENUM('buyit','tryit','avoid','unrated') DEFAULT 'unrated',
  external_links  JSON,                             -- { wiki_en: "...", wiki_jp: "...", baha: "..." }
  source_origin   VARCHAR(64),                      -- 'pcengine.co.uk','wikipedia','famicom.tw'
  view_count      INT UNSIGNED DEFAULT 0,
  cover_image_id  BIGINT UNSIGNED,                  -- → images.id
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,

  CONSTRAINT fk_games_console FOREIGN KEY (console_id) REFERENCES consoles(id),
  CONSTRAINT fk_games_primary_title FOREIGN KEY (primary_title_id) REFERENCES titles(id) ON DELETE SET NULL,
  CONSTRAINT fk_games_cover FOREIGN KEY (cover_image_id) REFERENCES images(id) ON DELETE SET NULL,
  UNIQUE KEY uq_console_slug (console_id, slug),
  INDEX idx_console_letter (console_id, letter),
  INDEX idx_format (format_category),
  INDEX idx_release_year (release_year),
  INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### 3.3 `titles`（多語標題，支援搜尋）
```sql
CREATE TABLE titles (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  game_id     BIGINT UNSIGNED NOT NULL,
  language    CHAR(3) NOT NULL,                     -- 'zh','en','jp','ko'
  text        VARCHAR(255) NOT NULL,
  is_aka      BOOLEAN DEFAULT FALSE,                -- aka = also-known-as / 別名
  source      VARCHAR(32),                          -- 'official','wiki','baha','user'
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,

  CONSTRAINT fk_titles_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
  INDEX idx_game_lang (game_id, language),
  FULLTEXT KEY ft_text (text) WITH PARSER ngram
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```
*ngram parser 讓中日文 FULLTEXT 搜尋能切詞。*

### 3.4 `descriptions`（簡介 / 評論）
```sql
CREATE TABLE descriptions (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  game_id     BIGINT UNSIGNED NOT NULL,
  kind        ENUM('overview','comment','plot','gameplay','review') NOT NULL,
  language    CHAR(3) NOT NULL,
  text        TEXT NOT NULL,
  source      VARCHAR(64) NOT NULL,                 -- 'pce_bible','wikipedia','screenscraper','user_xxx','ai_translate'
  source_url  VARCHAR(512),
  is_primary  BOOLEAN DEFAULT FALSE,                -- 該語言該 kind 的首選版本
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,

  CONSTRAINT fk_desc_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
  INDEX idx_game_kind_lang (game_id, kind, language, is_primary),
  FULLTEXT KEY ft_text (text) WITH PARSER ngram
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```
**重點**：同一遊戲、同一語言可以有多個來源（PCE Bible + Wiki + AI），用 `is_primary` 決定 detail page 預設展開哪個。

### 3.5 `images`
```sql
CREATE TABLE images (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  game_id     BIGINT UNSIGNED NOT NULL,
  kind        ENUM('cover','title_screen','snap','marquee','wheel','box_back','cart','manual') NOT NULL,
  url         VARCHAR(1024) NOT NULL,
  thumb_url   VARCHAR(1024),
  width       SMALLINT UNSIGNED,
  height      SMALLINT UNSIGNED,
  source      VARCHAR(64),                          -- 'libretro','pcengine.co.uk','famicom.tw','user_upload'
  region      CHAR(3),                              -- 'jp','us','eu','wor' （只對 cover 有意義）
  sort_order  TINYINT UNSIGNED DEFAULT 0,
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,

  CONSTRAINT fk_images_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
  INDEX idx_game_kind (game_id, kind, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### 3.6 `videos`
```sql
CREATE TABLE videos (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  game_id     BIGINT UNSIGNED NOT NULL,
  provider    VARCHAR(16) DEFAULT 'youtube',
  external_id VARCHAR(64) NOT NULL,                 -- youtube video id 11 chars
  title       VARCHAR(255),
  thumb_url   VARCHAR(512),                         -- generated from external_id
  duration_sec INT UNSIGNED,
  source      VARCHAR(32),                          -- 'editor','user','auto_match'
  sort_order  TINYINT UNSIGNED DEFAULT 0,

  CONSTRAINT fk_videos_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
  INDEX idx_game (game_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### 3.7 `genres` + `game_genres`
```sql
CREATE TABLE genres (
  id          SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  slug        VARCHAR(32) NOT NULL UNIQUE,           -- 'platformer','rpg','shooter'
  name_en     VARCHAR(64) NOT NULL,
  name_zh     VARCHAR(64) NOT NULL,
  parent_id   SMALLINT UNSIGNED,                     -- 'action_rpg' parent='rpg'
  sort_order  SMALLINT UNSIGNED DEFAULT 0,
  CONSTRAINT fk_genre_parent FOREIGN KEY (parent_id) REFERENCES genres(id) ON DELETE SET NULL
);

CREATE TABLE game_genres (
  game_id     BIGINT UNSIGNED NOT NULL,
  genre_id    SMALLINT UNSIGNED NOT NULL,
  is_primary  BOOLEAN DEFAULT FALSE,
  PRIMARY KEY (game_id, genre_id),
  CONSTRAINT fk_gg_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
  CONSTRAINT fk_gg_genre FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE,
  INDEX idx_genre (genre_id, is_primary)
) ENGINE=InnoDB;
```

### 3.8 `regions` + `game_regions`
```sql
CREATE TABLE regions (
  id      TINYINT UNSIGNED PRIMARY KEY,
  code    CHAR(3) NOT NULL UNIQUE,                   -- 'jp','na','eu','kr','asi','wor'
  name_zh VARCHAR(32) NOT NULL,
  flag_emoji VARCHAR(8)                              -- '🇯🇵'
);

CREATE TABLE game_regions (
  game_id      BIGINT UNSIGNED NOT NULL,
  region_id    TINYINT UNSIGNED NOT NULL,
  release_date DATE,
  product_code VARCHAR(64),                          -- 'AGB-AAAJ-JPN' 之類
  PRIMARY KEY (game_id, region_id),
  CONSTRAINT fk_gr_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
  CONSTRAINT fk_gr_region FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE,
  INDEX idx_region (region_id)
) ENGINE=InnoDB;
```

### 3.9 `users`
```sql
CREATE TABLE users (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  email           VARCHAR(255) NOT NULL UNIQUE,
  email_verified_at TIMESTAMP NULL,
  password        VARCHAR(255),                      -- nullable: OAuth-only users
  display_name    VARCHAR(64) NOT NULL,
  avatar_url      VARCHAR(512),
  google_id       VARCHAR(64) UNIQUE,                -- OAuth subject id
  discord_id      VARCHAR(64) UNIQUE,
  -- 投稿者統計（denormalized for speed）
  contrib_total       INT UNSIGNED DEFAULT 0,
  contrib_approved    INT UNSIGNED DEFAULT 0,
  contrib_rejected    INT UNSIGNED DEFAULT 0,
  reputation          INT DEFAULT 0,                 -- 自動算分用
  banned_until        TIMESTAMP NULL,
  ban_reason          VARCHAR(255),
  last_login_at       TIMESTAMP NULL,
  last_login_ip       VARBINARY(16),
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  INDEX idx_reputation (reputation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.10 RBAC（spatie/laravel-permission 自動建）

- `roles` (id, name, guard_name)：'user', 'contributor', 'moderator', 'admin'
- `permissions` (id, name, guard_name)：'contribution.create', 'contribution.approve', 'admin.access', ...
- `model_has_roles` (role_id, model_type, model_id)
- `model_has_permissions`（直接給某 user 額外權限）
- `role_has_permissions`

### 3.11 `contributions`
```sql
CREATE TABLE contributions (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id     BIGINT UNSIGNED NOT NULL,
  game_id     BIGINT UNSIGNED,                       -- nullable for "add new game"
  console_id  TINYINT UNSIGNED,                      -- 對應主機（投稿新遊戲時必填）
  type        ENUM('add_game','edit_field','add_translation','add_image','add_video','report_error') NOT NULL,
  target_field VARCHAR(64),                          -- 'title_zh','overview_zh','cover',...
  payload     JSON NOT NULL,                         -- 投稿的內容
  source_url  VARCHAR(512),                          -- 使用者標註的來源
  comment     TEXT,                                  -- 投稿者留言
  status      ENUM('pending','approved','rejected','withdrawn','duplicate') DEFAULT 'pending',
  reviewed_by BIGINT UNSIGNED,                       -- moderator id
  reviewed_at TIMESTAMP NULL,
  rejection_reason VARCHAR(255),
  applied_at  TIMESTAMP NULL,                        -- 套用到 games 的時間
  ip          VARBINARY(16),
  user_agent  VARCHAR(255),
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,

  CONSTRAINT fk_contrib_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_contrib_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL,
  CONSTRAINT fk_contrib_console FOREIGN KEY (console_id) REFERENCES consoles(id),
  CONSTRAINT fk_contrib_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_status_created (status, created_at),
  INDEX idx_user (user_id, status),
  INDEX idx_reviewer (reviewed_by, reviewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

`payload` JSON 範例（type=edit_field）：
```json
{
  "field": "title_zh",
  "old_value": "瑪利歐",
  "new_value": "瑪利歐進化"
}
```

`payload` JSON 範例（type=add_image）：
```json
{
  "kind": "cover",
  "region": "jp",
  "uploaded_path": "uploads/2026/04/abc123.jpg",
  "credit": "official Famitsu scan"
}
```

### 3.12 `contribution_attachments`
```sql
CREATE TABLE contribution_attachments (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  contribution_id BIGINT UNSIGNED NOT NULL,
  storage_path    VARCHAR(512) NOT NULL,             -- R2 object key
  mime_type       VARCHAR(64),
  size_bytes      INT UNSIGNED,
  width           SMALLINT UNSIGNED,
  height          SMALLINT UNSIGNED,
  CONSTRAINT fk_ca_contrib FOREIGN KEY (contribution_id) REFERENCES contributions(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### 3.13 `audit_logs`（spatie/activitylog 自動建表）
記錄誰在何時做了什麼：
- `causer_id` = 操作者 user
- `subject_type` / `subject_id` = 被操作的 model（例如 game / contribution）
- `description` = 'updated', 'approved', 'banned', etc.
- `properties` JSON = 變更前後的快照

---

## 4. 索引策略

### 高頻查詢
1. **列表頁**：`SELECT * FROM games WHERE console_id=? ORDER BY letter, slug LIMIT 24`
   → `idx_console_letter`
2. **搜尋**：`SELECT g.* FROM games g JOIN titles t ON t.game_id=g.id WHERE MATCH(t.text) AGAINST(?)`
   → FULLTEXT on `titles.text`
3. **篩類型 + 主機**：`WHERE console_id=? AND id IN (SELECT game_id FROM game_genres WHERE genre_id=?)`
   → `game_genres(genre_id, is_primary)` 已建
4. **未審投稿**：`WHERE status='pending' ORDER BY created_at`
   → `idx_status_created`

### 反例（不建）
- `games.maker` 不建索引（基數低、低頻過濾）
- `users.last_login_ip` 不建索引（合規 / 隱私考量）

---

## 5. 容量估算

| 表 | 預估 row 數 | 大小 |
|---|---|---|
| games | 6,000 | ~6 MB |
| titles | 18,000（每款 2-3 語言） | ~3 MB |
| descriptions | 30,000（中英多版本） | ~30 MB |
| images | 30,000（每款 5+） | ~10 MB |
| contributions | 1,000 / 月（活躍時） | ~1 MB / 月 |
| audit_logs | 5,000 / 月 | ~3 MB / 月 |

第一年 DB ~150 MB，輕鬆。

---

## 6. 從現有 JSON 匯入

`php artisan rgd:import-from-json`：

```php
// pseudocode
foreach (['pce','gba','fc','gb','gbc'] as $consoleSlug) {
    $games = json_decode(file_get_contents("data/{$consoleSlug}/games.json"));
    foreach ($games as $g) {
        Game::create([
            'console_id' => Console::where('slug',$consoleSlug)->first()->id,
            'slug' => $g->id,
            'no_intro_name' => $g->no_intro_name ?? null,
            'maker' => $g->maker ?? null,
            ...
        ]);
        // titles, descriptions, images, ...
    }
}
```

詳細搬遷流程 → 看 `07-Migration-Plan.md`

---

## 7. 待決定

1. `slug` 衝突要怎麼處理？目前 v2 有過手動加 `_2` 後綴，正式 schema 要不要強制 unique check？
2. 多語標題用 `titles` 子表 vs games 表上 4 個欄位（簡化方案）？  
   → **建議子表**（彈性高，未來加韓語、葡語不用改 schema）
3. ROM hash（CRC32 / MD5 / SHA1）要不要加進 games 表？  
   → **建議加**，未來可對接 RetroArch / Batocera scraper（見 No-Intro 對接需求）
4. 投稿過期時間？（pending 狀態太久要不要自動 expire？）
