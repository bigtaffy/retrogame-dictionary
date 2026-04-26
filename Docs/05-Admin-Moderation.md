# 05 — Admin Moderation（管理員審核）

> 管理員 / 編輯（moderator）如何處理投稿佇列。包含工作流程、UI 設計、批次操作。

---

## 1. 角色

| Role | 權限 |
|---|---|
| **moderator** | 審核投稿（approve / reject）、看 audit log |
| **admin** | 全部 + 直接 CRUD games、使用者管理、系統設定 |

兩種都可進後台，但看到的 menu 不同。

---

## 2. 投稿狀態機

```
        submit
         ↓
    ┌────────┐
    │pending │ ←─── (可由投稿者撤回 → withdrawn)
    └────────┘
       │     │
   approve  reject
       │     │
       ▼     ▼
    ┌────────┐    ┌────────┐
    │approved│    │rejected│
    └────────┘    └────────┘
       │
   apply (auto job)
       │
       ▼
    ┌────────┐
    │applied │  (寫入 games / descriptions / images)
    └────────┘

       (or)
    ┌──────────┐
    │duplicate │  (mark 為跟另一筆 contribution 重複)
    └──────────┘
```

**applied**：approved 後 backend job 把 payload 套用到正式表，套用成功才標 applied。失敗的會 alarm 通知 admin。

---

## 3. 審核佇列 UI（Filament）

### 3.1 列表頁

```
┌──────────────────────────────────────────────────────────────────┐
│  Moderation Queue                          [新進 47] [審核中 0]    │
│  ──────────────────────────────────────────────────────────────  │
│  [篩選: 主機 ▾] [類型 ▾] [使用者 ▾] [日期 ▾]    [批次操作 ▾]      │
│                                                                  │
│  ☐ #1024  ⏳ 5 分鐘前   GBA · 銀河戰士融合 · title_zh             │
│           Taffy (rep:38) ─ 「銀河戰士：融合」                     │
│           [✓ 通過] [✗ 駁回] [👁 詳情]                            │
│                                                                  │
│  ☐ #1023  ⏳ 12 分鐘前  PCE · 1941 · cover                      │
│           Bobby (rep:5) ─ 上傳了 320x480 jpg                    │
│           [預覽圖片]                                             │
│           [✓ 通過] [✗ 駁回] [👁 詳情]                            │
│                                                                  │
│  ...                                                             │
│  [上一頁]  Page 1 of 4  [下一頁]                                 │
└──────────────────────────────────────────────────────────────────┘
```

每列 1 行，hover 後展開 inline 預覽差異。

### 3.2 詳情頁（單筆投稿）

```
┌──────────────────────────────────────────────────────────────────┐
│  Contribution #1024                                              │
│  ──────────────────────────────────────────                     │
│  投稿者：Taffy (id 12, reputation 38)  通過 38 / 駁回 3           │
│  時間：2026-04-26 14:23 (5 分鐘前)                               │
│  IP / UA：220.140.x.x · Chrome/119 · Mac                        │
│                                                                  │
│  目標：GBA · 銀河戰士融合 (id 1234)                               │
│  類型：edit_field                                                │
│  欄位：title_zh                                                  │
│                                                                  │
│  變更：                                                          │
│  ┌────────────────────────────────────────────┐                 │
│  │ 舊：銀河戰士融合                           │                 │
│  │ 新：銀河戰士：融合                         │  ← diff highlight│
│  └────────────────────────────────────────────┘                 │
│                                                                  │
│  投稿者留言：「維基百科有冒號」                                   │
│  資料來源：https://zh.wikipedia.org/wiki/銀河戰士融合 ↗            │
│                                                                  │
│  ─────── 管理員區 ───────                                        │
│  Moderator note (內部留言)：                                     │
│  ┌────────────────────────────────────────────┐                 │
│  │                                            │                 │
│  └────────────────────────────────────────────┘                 │
│                                                                  │
│   [ ✓ 通過 ]   [ ✗ 駁回 ▾ ]   [ 標記重複 ]  [ 退回 user 補資料 ]  │
│                  ├─ 來源不可信                                   │
│                  ├─ 已有人投相同                                 │
│                  ├─ 不符規範                                     │
│                  └─ 自訂理由...                                  │
└──────────────────────────────────────────────────────────────────┘
```

### 3.3 圖片投稿的詳情

```
┌──────────────────────────────────────────────────────────────────┐
│  Contribution #1023 - cover image                                │
│  ──────────────────────────────                                 │
│  目標：PCE · 1941 (id 567)                                       │
│  目前封面：[old cover thumbnail]                                 │
│  使用者投稿：                                                    │
│  ┌─────────────┐ ┌─────────────────────────────┐                │
│  │             │ │ 320×480 · JPEG · 89 KB       │                │
│  │  上傳的     │ │ source: 自己掃描              │                │
│  │  原圖預覽   │ │ region: jp                  │                │
│  │             │ │                             │                │
│  └─────────────┘ └─────────────────────────────┘                │
│                                                                  │
│  比較模式：[並排] [覆蓋] [切換]                                  │
│                                                                  │
│  [✓ 通過：取代 cover]  [✓ 通過：加入 box scans]  [✗ 駁回 ▾]      │
└──────────────────────────────────────────────────────────────────┘
```

通過時可選：
- 取代主封面（取代既有 cover）
- 加入 box scans（保留舊封面，新增區域 cover）

---

## 4. 審核動作（後端流程）

### 4.1 Approve

```
moderator clicks 「✓ 通過」
   ↓
Backend：
  1. contributions.status = 'approved'
  2. contributions.reviewed_by = moderator.id
  3. contributions.reviewed_at = now
  4. queue ApplyContribution job
  5. log activity
   ↓
ApplyContribution job：
  - type=edit_field：UPDATE games / titles / ... SET (target_field) = (new_value)
  - type=add_translation：INSERT INTO titles
  - type=add_image：INSERT INTO images，原檔從 contribution_attachments 移到 production
  - type=add_video：INSERT INTO videos
  - type=add_game：INSERT INTO games + 關聯表
  失敗：raise alarm (Sentry)，狀態退回 pending + 寫 admin note
   ↓
成功：
  - contributions.applied_at = now
  - users.contrib_approved += 1, reputation += 1
  - 寄信 / 站內訊息給投稿者
  - 重建 cache（清掉該 game 的快取）
```

### 4.2 Reject

```
moderator clicks 「✗ 駁回」並選原因
   ↓
contributions.status = 'rejected'
contributions.rejection_reason = '...'
contributions.reviewed_by/at = ...
users.contrib_rejected += 1, reputation -= 2
寄信 / 站內訊息（含 reason）
不套用任何資料
```

### 4.3 Mark duplicate

合併 N 筆相同 / 相似投稿到一個「主」投稿，其他標 duplicate（不扣分、不通知，等主投稿通過/駁回時連動處理）。

---

## 5. 批次操作

選多筆投稿後：

| 動作 | 適用 |
|---|---|
| 全部通過 | 信任的高聲望用戶批次小修 |
| 全部駁回（同一原因） | spam 集中處理 |
| 全部標 duplicate | 重複投稿 |
| 重新指派給其他 mod | 換手審核 |

---

## 6. Smart suggestions（節省 mod 時間）

審核 UI 自動顯示輔助訊息，幫 mod 快速判斷：

| 觸發 | 顯示 |
|---|---|
| 用戶 reputation 高 | 🟢「老 user，可信」 |
| 用戶曾被駁很多次 | 🟡「最近駁過 3 次」 |
| 同一遊戲已有 pending | 🔵「同遊戲還有 1 筆待審」 |
| 投稿內容跟已有資料完全相同 | ⚪「跟現有資料一致，疑似誤觸」 |
| 圖片很模糊 / 解析度低 | ⚠️「解析度 < 240×320」 |
| 來源網址是 wiki / 巴哈 / IGDB | 🟢 顯示 favicon 暗示「來源可考」 |
| AI 自動翻譯偵測（高概率） | 🤖「疑似 AI 翻譯」（mod 可選擇相信或不相信） |

---

## 7. 通知 / 工作分配

### 7.1 新投稿通知到 mod

選一：
- **Email digest**：每天 9am 寄一封「今日 47 筆待審」+ 直連
- **Slack / Discord webhook**：每筆即時通知 channel
- **Telegram bot**：mod 多人共用一個 group，每筆轉發

### 7.2 工作分配（多 mod）

- **單人模式**（1 個 admin）：不分配，逐筆處理
- **多人模式**（≥ 2 mods）：佇列頁加「指派給我 / 釋放回佇列」按鈕
  - mod 點「指派給我」→ contributions.reviewed_by = me（暫定）
  - 別人就看不到該筆（避免重複處理）
  - 30 分鐘沒處理 → 自動釋放回佇列
  - 處理完才寫 reviewed_at

---

## 8. Audit log（誰改了什麼）

每次 approve / reject / direct-edit 都進 `audit_logs`：

```
[2026-04-26 15:42] admin Bob approved contribution #1024
  subject: Game(1234)
  changes: { title_zh: "銀河戰士融合" → "銀河戰士：融合" }
  via: contribution submitted by user Taffy (12)

[2026-04-26 15:50] admin Bob direct-edited Game(567).maker
  before: "Capcom"
  after:  "Capcom Co., Ltd."
  reason: "正規化廠商名"
```

Admin 在「Audit Log」頁可篩選 actor / subject / 時間範圍。

---

## 9. 駁回原因標準化（避免每次手寫）

預設駁回原因下拉：

| 代碼 | 原因 |
|---|---|
| `incorrect_source` | 來源不可信或不存在 |
| `low_quality_image` | 圖片解析度 / 品質太低 |
| `duplicate` | 已有相同投稿 |
| `not_relevant` | 跟該遊戲無關 |
| `wrong_game` | 投到錯遊戲（疑似搞錯） |
| `policy_violation` | 違反站規（廣告、人身攻擊） |
| `incomplete` | 資訊不完整 |
| `language_wrong` | 語言錯誤（例：投英文標題到中文欄位） |
| `other` | 其他（要填自由文字） |

通知信會根據代碼用標準範本，再附上 admin 自由文字。

---

## 10. 資料還原（Rollback）

如果 approve 後發現是錯的：

1. Admin 進該 contribution 詳情頁
2. 按「回復此變更」
3. backend：
   - 從 audit_log 取出 before snapshot
   - INSERT 回去 / UPDATE 回原值
   - 標 contribution.status = 'rolled_back'（新 ENUM 值）
   - 寄信給原投稿者「你的投稿因 X 原因被回復」

---

## 11. KPI / 服務水準

衡量 mod 效率：

- 待審佇列平均積壓時間（目標 < 24h）
- 通過率 / 駁回率（目標 60-80% 通過）
- 平均處理時間（目標 < 2 分鐘 / 筆）
- 投稿者滿意度（駁回後 user 是否再次投稿）

Dashboard 在 06-Admin-Backoffice.md 詳述。

---

## 12. 開放問題

1. **公開審核紀錄？** 一般使用者要不要看得到「該遊戲最近被改過 N 次、由誰、改了什麼」？
   - 建議：**部分公開**。游標 hover 顯示「最後更新 X 天前 by user」，但內容變更不公開（避免揭露 mod 身份）。

2. **管理員自己投稿要不要進佇列？**
   - 建議：**不進佇列，直接寫入**，但 audit_log 標明「by admin」（增強透明度）。

3. **被駁的投稿要不要保留？**
   - 建議：**保留 90 天**後 hard delete（GDPR / 隱私考量）。
   - 統計用：保留 anon 化的 hash + 駁回原因，給 mod 訓練用。

4. **多 mod 衝突解決？**
   - 兩個 mod 同時看同一筆，先按下的贏（pessimistic lock 或 optimistic version check）。

5. **AI 輔助審核？**
   - 未來可加：AI 自動標「疑似錯字」「疑似 AI 翻譯」「圖品質分」幫 mod。
   - v1 先不做。

