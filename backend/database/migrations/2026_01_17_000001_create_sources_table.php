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
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('来源名称');
            $table->string('type', 50)->comment('来源类型，如 blog/changelog');
            $table->string('feed_url')->comment('RSS/Atom 地址');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->timestamp('last_fetched_at')->nullable()->comment('最近一次采集时间');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
