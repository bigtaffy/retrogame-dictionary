<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 對應 Docs/12-Admin-UI-Design.md 與 cheats schema 設計。
 *
 * 紀錄每個遊戲的秘技（按鈕組合 / 密碼 / Game Genie / Pro Action Replay /
 * memory patch / glitch / 隱藏內容 / 解鎖條件 等多種類型）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();

            // 種類 — 不同 type 在 UI 用不同 icon/顏色
            $table->enum('type', [
                'button_sequence',    // 上上下下←→←→BA 按鈕序列
                'password',           // 遊戲內輸入密碼
                'game_genie',         // Game Genie 卡夾 hex code
                'pro_action_replay',  // PAR / GameShark / 模擬器 cheat
                'memory_patch',       // 直接改 RAM 位置
                'glitch',             // 利用 bug 達成的技巧
                'easter_egg',         // 隱藏致敬 / 彩蛋
                'unlock',             // 達成條件解鎖
                'misc',
            ])->index();

            // 效果敘述（多語）
            $table->string('effect_zh', 255)->nullable();
            $table->string('effect_en', 255)->nullable();
            $table->string('effect_jp', 255)->nullable();

            // 秘技本體
            $table->text('code')->nullable();
            $table->string('code_normalized', 255)->nullable();

            // 觸發條件
            $table->string('trigger_at', 128)->nullable();
            $table->string('rom_version', 32)->nullable();
            $table->char('region', 2)->nullable();

            // 詳細說明（Markdown 允許）
            $table->text('description_zh')->nullable();
            $table->text('description_en')->nullable();

            // 困難度
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'speedrun_only', 'tas_only'])
                ->default('easy');

            // 來源
            $table->string('source', 64)->nullable();
            $table->string('source_url', 512)->nullable();
            $table->foreignId('contributor_id')->nullable()->constrained('users')->nullOnDelete();

            // 審核
            $table->boolean('verified')->default(false)->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['game_id', 'type', 'sort_order']);
        });

        // FULLTEXT
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            try {
                DB::statement(
                    'ALTER TABLE cheats ADD FULLTEXT KEY ft_cheat_text '
                    .'(effect_zh, effect_en, effect_jp, description_zh, description_en) WITH PARSER ngram'
                );
            } catch (\Throwable) {
                try {
                    DB::statement(
                        'ALTER TABLE cheats ADD FULLTEXT KEY ft_cheat_text '
                        .'(effect_zh, effect_en, effect_jp, description_zh, description_en)'
                    );
                } catch (\Throwable) {
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cheats');
    }
};
