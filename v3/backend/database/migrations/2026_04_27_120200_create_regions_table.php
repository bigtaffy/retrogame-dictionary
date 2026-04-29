<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 對應 Docs/02-Database-Schema.md §3.8 `regions`
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->char('code', 3);
            $table->string('name_zh', 32);
            $table->string('flag_emoji', 8)->nullable();
        });

        DB::table('regions')->insert([
            ['id' => 1, 'code' => 'jp', 'name_zh' => '日本', 'flag_emoji' => '🇯🇵'],
            ['id' => 2, 'code' => 'na', 'name_zh' => '北美', 'flag_emoji' => '🇺🇸'],
            ['id' => 3, 'code' => 'eu', 'name_zh' => '歐洲', 'flag_emoji' => '🇪🇺'],
            ['id' => 4, 'code' => 'wor', 'name_zh' => '世界', 'flag_emoji' => null],
            ['id' => 5, 'code' => 'kr', 'name_zh' => '韓國', 'flag_emoji' => '🇰🇷'],
            ['id' => 6, 'code' => 'asi', 'name_zh' => '亞洲', 'flag_emoji' => null],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
