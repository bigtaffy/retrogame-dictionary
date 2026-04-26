# 08 — Genre Taxonomy（遊戲類型分類重設計）

> 現有資料 65 個不同 genre 值散落在 6,293 款遊戲裡。重新整理成乾淨的兩層分類，並提供 migration 對照表。

---

## 1. 問題現況

### 1.1 來源散亂
- **PCE**：原始來源 pcengine.co.uk + 巴哈姆特，巴哈用「動作 (ACT)」這類含 TLA 縮寫的格式
- **GBA / GB / GBC**：libretro-database genre DAT，英文，部分翻成中文（不完整）
- **FC**：多數沒 genre（match rate 6.6%）

### 1.2 同義不同寫
```
動作               375
動作 (ACT)         434
動作 Action         73   ← 三個都是同一件事
```

### 1.3 子類型混進主類型
```
動作平台 717        ← 子類型，不該跟「動作」並列
清版動作  79        ← 子類型
彈幕射擊  93        ← 是「射擊」的子型
```

### 1.4 不一致的「Other」
```
其他 Other 219
其他 (TBG) 72       ← Table Game Board game?
其他 (ECT) 63       ← Etc?
其他 (SRPG) 1       ← 應該是「戰略角色扮演」不是 other
```

### 1.5 中英 / 簡縮混雜
- `成人 Adult`
- `射擊 Shoot 'em Up`
- `麻將博奕 Mahjong/Gambling`

### 1.6 缺值
- 188 款 (3%) 空 string
- FC 1,160 款（93%）沒 genre

---

## 2. 設計目標

1. **使用者面**：下拉選單最多 15 項主類型，每項清楚（不再混雜縮寫 / 中英）
2. **後端面**：用 `genres` + `game_genres` 樞紐表，每款遊戲 1 主類型 + N 標籤
3. **可擴充**：新類型由 admin 後台加，不用改 schema
4. **可逆**：保留原始字串在 `genre_raw` 欄位給除錯 / migration 校對
5. **多語**：中英並存（資料庫存中英；UI 跟著 user 語言）

---

## 3. 兩層分類（新版）

### 3.1 主類型（17 個 = 下拉預設項目）

| slug | 中 | EN | 範例（5 個主機常見） |
|---|---|---|---|
| `action` | 動作 | Action | Castlevania, Ninja Gaiden, Contra |
| `adventure` | 冒險 | Adventure | Zelda, Maniac Mansion |
| `rpg` | 角色扮演 | RPG | Final Fantasy, Pokemon, Mother |
| `strategy` | 策略 | Strategy | Fire Emblem, Advance Wars, Nobunaga |
| `simulation` | 模擬 | Simulation | Princess Maker, Tokimeki Memorial |
| `racing` | 競速 | Racing | F-Zero, Mario Kart |
| `sports` | 運動 | Sports | Tennis, World Cup |
| `fighting` | 格鬥 | Fighting | Street Fighter, Mortal Kombat |
| `shooter` | 射擊 | Shooter | Gradius, R-Type, 1942 |
| `puzzle` | 解謎 | Puzzle | Tetris, Lemmings |
| `platformer` | 平台 | Platformer | Super Mario, Sonic |
| `music` | 音樂節奏 | Music / Rhythm | Beatmania, DDR |
| `board_card` | 桌遊紙牌 | Board / Card | 麻將, 將棋, 圍棋 |
| `educational` | 教育 | Educational | Doraemon Math, Reader Rabbit |
| `casino` | 博弈 | Casino | 柏青哥, Slots |
| `compilation` | 合輯 | Compilation | Capcom Classics, Mario Bros + DH |
| `other` | 其他 | Other | （只給真的無法分類用） |

### 3.2 子類型（tag，0-N 個 / 遊戲）

| slug | parent | 中 | EN |
|---|---|---|---|
| `action_platformer` | action | 動作平台 | Action Platformer |
| `beat_em_up` | action | 清版動作 | Beat 'em Up |
| `action_adventure` | action | 動作冒險 | Action-Adventure |
| `action_rpg` | rpg | 動作角扮 | Action RPG |
| `tactical_rpg` | rpg | 戰略角扮 | Tactical RPG (SRPG) |
| `jrpg` | rpg | 日式角扮 | JRPG |
| `dungeon_crawler` | rpg | 迷宮探索 | Dungeon Crawler |
| `visual_novel` | adventure | 視覺小說 | Visual Novel |
| `point_and_click` | adventure | 點擊冒險 | Point-and-Click |
| `dating_sim` | simulation | 戀愛模擬 | Dating Sim |
| `life_sim` | simulation | 養成模擬 | Life Sim |
| `flight_sim` | simulation | 飛行模擬 | Flight Sim |
| `tycoon` | simulation | 經營策略 | Tycoon / Management |
| `kart_racing` | racing | 卡丁車 | Kart Racer |
| `arcade_racing` | racing | 街機競速 | Arcade Racing |
| `tennis` | sports | 網球 | Tennis |
| `golf` | sports | 高爾夫 | Golf |
| `baseball` | sports | 棒球 | Baseball |
| `soccer` | sports | 足球 | Soccer |
| `boxing` | sports | 拳擊 | Boxing |
| `versus_fighting` | fighting | 對戰格鬥 | Versus Fighter |
| `wrestling` | fighting | 摔角 | Wrestling |
| `shoot_em_up` | shooter | 彈幕射擊 | Shoot 'em Up |
| `run_and_gun` | shooter | 跑射 | Run-and-Gun |
| `fps` | shooter | 第一人稱射擊 | First-Person Shooter |
| `tps` | shooter | 第三人稱射擊 | Third-Person Shooter |
| `light_gun` | shooter | 光線槍 | Light Gun |
| `tile_matching` | puzzle | 連消益智 | Tile Matching |
| `physics_puzzle` | puzzle | 物理益智 | Physics |
| `quiz` | puzzle | 益智問答 | Quiz |
| `mahjong` | board_card | 麻將 | Mahjong |
| `chess_shogi` | board_card | 西洋棋 / 將棋 | Chess / Shogi |
| `card_battle` | board_card | 卡牌對戰 | Card Battle |
| `tcg` | board_card | 集換式卡牌 | TCG |
| `pinball` | other | 彈珠台 | Pinball |
| `hunting_fishing` | sports | 狩獵釣魚 | Hunting & Fishing |
| `adult` | other | 成人 | Adult / Hentai |
| `homebrew` | other | 同人創作 | Homebrew |

### 3.3 這樣分的好處

- 主類型穩定（17 個不會變）
- 子類型可隨 catalog 成長慢慢加
- 「動作平台」現在進 `action_platformer`，主類型自動歸 `action` ── 跟「動作」合併計算
- 篩選 UI 兩段式：先選主類型，再可選子類型 chips
- 「其他」恢復成它該是的小桶子（之前 219 + 雜七雜八 = 410 筆其實只要 < 50）

---

## 4. Migration 對照表

從現有 65 個亂值 → 新分類：

| 原值 | → 主類型 | → 子類型（可選） |
|---|---|---|
| `動作平台` (717) | action | action_platformer |
| `運動` (478) | sports | — |
| `角色扮演` (472) | rpg | — |
| `動作 (ACT)` (434) | action | — |
| `動作` (375) | action | — |
| `動作 Action` (73) | action | — |
| `冒險` (284) | adventure | — |
| `競速` (257) | racing | — |
| `策略` (230) | strategy | — |
| `其他 Other` (219) | other | — |
| `解謎` (190) | puzzle | — |
| `(空)` (188) | **未分類**（後台補） | — |
| `合輯` (181) | compilation | — |
| `運動 (SPG)` (167) | sports | — |
| `棋盤桌遊` (155) | board_card | — |
| `角色扮演 (RPG)` (138) | rpg | — |
| `模擬` (134) | simulation | — |
| `射擊 (STG)` (109) | shooter | — |
| `射擊` (103) | shooter | — |
| `彈幕射擊` (93) | shooter | shoot_em_up |
| `角色扮演 RPG` (85) | rpg | — |
| `格鬥` (83) | fighting | — |
| `清版動作` (79) | action | beat_em_up |
| `益智 (PZG)` (75) | puzzle | — |
| `策略 (SLG)` (75) | strategy | — |
| `其他 (TBG)` (72) | board_card | — |
| `冒險 (ADV)` (69) | adventure | — |
| `冒險 Adventure` (65) | adventure | — |
| `其他 (ETC)` (63) | other | — |
| `動作角色扮演 (ARPG)` (60) | rpg | action_rpg |
| `射擊 Shoot 'em Up` (54) | shooter | shoot_em_up |
| `策略模擬 Strategy` (50) | strategy | — |
| `紙牌` (46) | board_card | — |
| `益智 Puzzle` (34) | puzzle | — |
| `運動競速 Sports` (33) | sports | — |
| `休閒` (32) | other | — |
| `益智問答` (30) | puzzle | quiz |
| `彈珠台` (28) | other | pinball |
| `狩獵釣魚` (28) | sports | hunting_fishing |
| `麻將博奕 Mahjong/Gambling` (27) | casino | mahjong |
| `博弈` (26) | casino | — |
| `音樂` (26) | music | — |
| `教育` (23) | educational | — |
| `格鬥 Fighting` (22) | fighting | — |
| `綜合` (22) | other | — |
| `冒險 (AVG)` (17) | adventure | — |
| `成人 Adult` (16) | other | adult |
| `競速 (RCG)` (8) | racing | — |
| `益智 (PUZ)` (7) | puzzle | — |
| `其他 (ECT)` (7) | other | — |
| `其他 (MOV)` (6) | other | — |
| `集換式卡牌 (TCG)` (5) | board_card | tcg |
| `格鬥 (FTG)` (4) | fighting | — |
| `桌遊 (TAB)` (4) | board_card | — |
| `益智` (4) | puzzle | — |
| `卡牌 (CAG)` (2) | board_card | — |
| `未分類` (1) | (空) | — |
| `休閒益智 (CBG)` (1) | puzzle | — |
| `其他 (RAC)` (1) | racing | — |
| `其他 (AAVG)` (1) | adventure | action_adventure |
| `其他 (SRPG)` (1) | rpg | tactical_rpg |
| `策略模擬 (SLG)` (1) | strategy | — |
| `其他 (TGB)` (1) | other | — |
| `其他 (ROM)` (1) | rpg | — |
| `其他 (ADC)` (1) | adventure | — |

合計能歸位的 = 6,105 / 6,293 (97%)，剩 188 真的空白要 admin 後台慢慢補。

---

## 5. Schema 對應

回到 `02-Database-Schema.md` 的 `genres` 表：

```sql
-- 17 主類型 + ~30 子類型 seeding
INSERT INTO genres (slug, name_zh, name_en, parent_id, sort_order) VALUES
  ('action',       '動作',     'Action',          NULL, 10),
  ('adventure',    '冒險',     'Adventure',       NULL, 20),
  ('rpg',          '角色扮演',  'RPG',             NULL, 30),
  ('strategy',     '策略',     'Strategy',        NULL, 40),
  ('simulation',   '模擬',     'Simulation',      NULL, 50),
  ('racing',       '競速',     'Racing',          NULL, 60),
  ('sports',       '運動',     'Sports',          NULL, 70),
  ('fighting',     '格鬥',     'Fighting',        NULL, 80),
  ('shooter',      '射擊',     'Shooter',         NULL, 90),
  ('puzzle',       '解謎',     'Puzzle',          NULL, 100),
  ('platformer',   '平台',     'Platformer',      NULL, 110),
  ('music',        '音樂節奏', 'Music / Rhythm',  NULL, 120),
  ('board_card',   '桌遊紙牌', 'Board / Card',    NULL, 130),
  ('educational',  '教育',     'Educational',     NULL, 140),
  ('casino',       '博弈',     'Casino',          NULL, 150),
  ('compilation',  '合輯',     'Compilation',     NULL, 160),
  ('other',        '其他',     'Other',           NULL, 999);

-- 子類型（parent_id 從上面查）
INSERT INTO genres (slug, name_zh, name_en, parent_id) VALUES
  ('action_platformer', '動作平台', 'Action Platformer', (SELECT id FROM genres WHERE slug='action')),
  ('beat_em_up',        '清版動作', 'Beat ''em Up',     (SELECT id FROM genres WHERE slug='action')),
  ...
```

每款遊戲在 `game_genres` 樞紐表存 1 個 primary（is_primary=1）+ 0..N tag。

---

## 6. 「動作平台 = 動作 + 平台」的設計選擇

兩種方法：

### 方法 A：動作平台是「動作」的子類型（單繼承）
- `action_platformer` parent_id = `action`
- 篩「動作」會包含「動作平台」
- 但 platform 不是獨立主類型

### 方法 B：動作平台同時屬於兩個 tag
- 主類型 = action，tag = platformer
- 篩「動作」拿到、篩「平台」也拿到
- 需要每款遊戲多筆 game_genres row

**建議方法 A**，理由：
- 大部分人選「動作」就想看到動作平台；不會單獨找「平台」
- UI 更簡單（17 主類型，子類型只當 chip 顯示）
- 萬一誰要單篩 platform，後台可加搜尋過濾

---

## 7. 未分類 (188 款) 怎麼處理

不主動猜，留空。然後：

- Admin 後台 → Coverage Gaps 頁列出「未分類」清單，按主機分組
- 投稿系統允許 user 投稿類型：「我覺得這款是 platformer」
- AI 輔助（v3 後期）：丟 title + maker + year 給 LLM 推薦類型，admin 確認

---

## 8. UI 影響

### 篩選下拉
```
舊：[所有類型 ▾]
   - 動作
   - 動作 (ACT)
   - 動作 Action
   - 動作平台
   - 動作角色扮演 (ARPG)
   ... (65 個)

新：[所有類型 ▾]
   - 動作 (1,278)
     ▸ 動作平台 (717)
     ▸ 清版動作 (79)
     ▸ 動作冒險 (...)
   - 角色扮演 (755)
     ▸ 動作角扮 (60)
     ▸ 戰略角扮 (...)
   - 射擊 (459)
     ▸ 彈幕射擊 (93)
   - ...
```

桌機可雙列（主類型左、子類型右側展開）。手機可二段式 modal。

### 卡片上的 badge
- 主類型 badge 寬一點、有色（依主類型 fixed color）
- 子類型 badge 細小、灰色

---

## 9. 落實步驟（v3 migration 時）

```
Step 1：建 genres 表 + 17 主類型 + ~30 子類型 seed
Step 2：建 game_genres 樞紐表
Step 3：跑 migration script，依本檔對照表把 65 個舊值對應到新 (genre, subgenre)
Step 4：每款 game：
  INSERT INTO game_genres (game_id, genre_id, is_primary) VALUES (..., main_id, 1)
  IF subgenre：INSERT INTO game_genres (game_id, genre_id, is_primary) VALUES (..., sub_id, 0)
Step 5：保留 games.genre_raw 欄位（debug 用，未來可刪）
Step 6：API GET /games/{id} 回新格式：
  { primary_genre: { slug:"action", name_zh:"動作" }, sub_genres: [...] }
Step 7：前端下拉重做（兩層）
Step 8：投稿系統允許 user propose 新增子類型（admin 審核）
```

---

## 10. 後續維護

- 每月 admin 回顧「其他」桶有沒有可拆出新主類型
- 每年大檢討：哪些子類型可升主類型 / 反之
- 由 admin 控制，不要讓 user 隨便新增主類型（避免再亂掉）

---

## 11. 開放問題

1. **「平台 platformer」要不要當主類型？**
   - GBA 717 款動作平台 + GB 150 款，量很大
   - 但放主類型會造成「動作」跟「平台」概念重疊
   - 建議：**保留動作為主，平台為子類型**（避免 user 困惑）。可加 quick filter「⚡ 平台跳跳」當捷徑。

2. **多 tag 還是單 tag？**
   - 一款遊戲可能同時是 action + adventure（如薩爾達）
   - 建議：**1 primary + 多 tag 可選**。primary 用於主篩選 / 卡片色，tag 用於搜尋 / 推薦。

3. **「成人 Adult」要不要 SFW 隱藏？**
   - PCE 有 16 款，GBA 沒有
   - 建議：**default 隱藏**，使用者帳號 setting 開「成人內容」才出現。法律 / 廣告風險小。

4. **AI 自動分類冷門遊戲？**
   - 188 個未分類 + FC 1160 個未分類
   - 建議 v3 後期做：丟 title + screenshot + maker 給 GPT-4 推測，admin 二次確認。

5. **要不要英文 only 模式？**
   - 系統一定中英都存。UI 取決於 user lang 設定。
