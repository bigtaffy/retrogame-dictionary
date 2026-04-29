<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 對應 Docs/02-Database-Schema.md §3.7 `genres`
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->autoIncrement()->primary();
            $table->string('slug', 32);
            $table->string('name_en', 64);
            $table->string('name_zh', 64);
            $table->unsignedSmallInteger('parent_id')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->unique('slug');
            $table->foreign('parent_id')->references('id')->on('genres')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genres');
    }
};
