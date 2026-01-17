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
        Schema::create('fetch_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('来源 ID');
            $table->string('status', 30)->comment('采集状态，如 success/failed');
            $table->timestamp('started_at')->comment('开始时间');
            $table->timestamp('finished_at')->nullable()->comment('结束时间');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fetch_runs');
    }
};
