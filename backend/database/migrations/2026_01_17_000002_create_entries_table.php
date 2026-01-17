<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('来源 ID');
            $table->string('title')->comment('条目标题');
            $table->string('url')->comment('原文链接');
            $table->timestamp('published_at')->comment('发布时间');
            $table->text('summary')->nullable()->comment('摘要');
            $table->longText('content')->nullable()->comment('正文内容');
            $table->string('dedupe_key')->comment('去重键（由来源与链接等生成）');
            $table->timestamps();

            $table->unique('dedupe_key');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
