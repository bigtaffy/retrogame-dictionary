# V3 Stage 1：秘技模型 + Filament Resource

分支：`v3-dev`（**不部 Cloudflare**）
目標：在 V3 後台完整支援「秘技」這個資源（CRUD + 嵌入到 Game Edit page）。

---

## 1. 已建檔案

| 檔案 | 用途 |
|------|------|
| `database/migrations/2026_04_29_140000_create_cheats_table.php` | cheats 表 + FULLTEXT |
| `app/Models/Cheat.php` | Eloquent + `normalizeCode()` mutator |
| `app/Models/Game.php` | 加 `cheats()` HasMany |
| `app/Filament/Resources/Cheats/CheatResource.php` | 秘技獨立 Resource |
| `app/Filament/Resources/Cheats/Schemas/CheatForm.php` | 4 Section 表單，多語 Tabs |
| `app/Filament/Resources/Cheats/Tables/CheatsTable.php` | 列表 + filter（種類 / 難度 / 主機 / 驗證 / 缺中文） |
| `app/Filament/Resources/Cheats/Pages/{ListCheats,CreateCheat,EditCheat}.php` | 三個標準 page |
| `app/Filament/Resources/Games/RelationManagers/CheatsRelationManager.php` | 嵌進 Edit Game |
| `app/Filament/Resources/Games/GameResource.php` | `getRelations()` 加 `CheatsRelationManager::class` |
| `database/seeders/CheatSeeder.php` | 灌 3 筆煙霧測試資料 |
| `tests/Unit/CheatModelTest.php` | normalizer + 常數單元測試 |

## 2. Schema 重點

```
cheats
├─ id
├─ game_id (FK → games, CASCADE)
├─ type (enum 9 種)
├─ difficulty (easy/medium/hard)
├─ region (jp/us/eu, nullable)
├─ rom_version (varchar 32)
├─ effect_zh / effect_en / effect_jp
├─ description_zh / description_en
├─ code (text)
├─ code_normalized (varchar 255, 自動產生 — dedupe key)
├─ trigger_at
├─ source / source_url
├─ contributor_id (FK → users, NULL)
├─ verified (bool) / verified_by (FK → users, NULL) / verified_at
├─ sort_order
└─ FULLTEXT(effect_zh, effect_en, description_zh, description_en) WITH PARSER ngram
```

## 3. 驗證腳本（每階段必跑）

```bash
cd v3/backend

# (1) migrate 不爆
php artisan migrate

# (2) seed 3 筆 demo 秘技
php artisan db:seed --class=CheatSeeder

# (3) tinker：driveby 確認關聯
php artisan tinker
>>> Cheat::with('game')->first();
>>> Cheat::pending()->count();    // 應該 ≥ 2
>>> Cheat::verified()->count();   // 應該 ≥ 1
>>> Game::find(1)->cheats->count();

# (4) 單元測試（不碰 DB）
./vendor/bin/phpunit tests/Unit/CheatModelTest.php

# (5) 後台肉眼驗
php artisan serve
# 開 http://localhost:8000/admin
# - 左側選單「目錄 Catalog」→「秘技」可看列表
# - 點某筆 game 的 Edit → 下方有「秘技 Cheats」relation tab
# - Create / Edit / Delete 都跑得起來
```

## 4. 還沒做的事（之後 Stage）

- 公開 read-only API（`/api/cheats?game=...`）→ Stage 3
- 前端 `<DetailPage>` 加「秘技」分頁 → 等公開 API 後做
- 自動 dedupe（`code_normalized` 撞同 game_id 不允許）→ 進 unique index 之前先放著看資料

---
