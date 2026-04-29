<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 對應 Docs/12-Admin-UI-Design.md 的 data_sources 表。
 * 紀錄外部資料源（libretro / TGDB / ScreenScraper / Wikipedia / 巴哈）的健康狀態。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();           // libretro_thumbs, tgdb_api, ss_api, wiki_zh ...
            $table->string('name', 128);
            $table->enum('kind', ['covers', 'metadata', 'descriptions', 'mixed']);
            $table->enum('status', ['ok', 'warning', 'error', 'disabled', 'unknown'])->default('unknown');
            $table->string('endpoint', 512)->nullable();
            $table->json('config')->nullable();             // {api_key_env: 'TGDB_KEY', rate_limit: 1000, ...}
            $table->json('coverage')->nullable();           // 每主機的 coverage 統計
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->string('last_error', 512)->nullable();
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('fail_count')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index('status');
            $table->index('kind');
        });

        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_source_id')->constrained()->cascadeOnDelete();
            // consoles.id is unsignedTinyInteger (see 2026_04_27_120000) — must match
            $table->unsignedTinyInteger('console_id')->nullable();
            $table->foreign('console_id')->references('id')->on('consoles')->nullOnDelete();
            $table->enum('status', ['running', 'success', 'partial', 'failed'])->default('running');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('items_attempted')->default(0);
            $table->unsignedInteger('items_succeeded')->default(0);
            $table->unsignedInteger('items_failed')->default(0);
            $table->json('summary')->nullable();
            $table->text('error_log')->nullable();
            $table->string('triggered_by', 128)->nullable();   // user email / 'cron'
            $table->timestamps();

            $table->index(['data_source_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
        Schema::dropIfExists('data_sources');
    }
};
