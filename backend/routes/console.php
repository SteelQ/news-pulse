<?php

use App\Models\Source;
use App\Services\FetchSourceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('news:fetch-source {source_id}', function (FetchSourceService $fetcher) {
    $sourceId = (int) $this->argument('source_id');
    $source = Source::query()->find($sourceId);

    if (! $source) {
        $this->error('来源不存在，无法执行采集。');

        return 1;
    }

    try {
        $result = $fetcher->fetch($source);
    } catch (Throwable $exception) {
        $this->error('采集失败：'.$exception->getMessage());

        return 1;
    }

    $message = "采集完成，新增 {$result['created_count']} 条。";
    if ($result['warning'] !== null) {
        $message .= " 注意：{$result['warning']}。";
    }

    $this->info($message);

    return 0;
})->purpose('采集指定来源的 RSS/Atom');

Artisan::command('news:fetch-enabled', function (FetchSourceService $fetcher) {
    $sources = Source::query()
        ->where('is_enabled', true)
        ->get();

    if ($sources->isEmpty()) {
        $this->info('没有启用的来源需要采集。');

        return 0;
    }

    $failedCount = 0;
    $createdCount = 0;

    foreach ($sources as $source) {
        try {
            $result = $fetcher->fetch($source);
            $createdCount += $result['created_count'];

            if ($result['warning'] !== null) {
                $this->warn("来源 {$source->id} 提示：{$result['warning']}。");
            }
        } catch (Throwable $exception) {
            // 单个来源失败时不中断整体采集，确保批量任务可继续推进。
            $failedCount++;
            $this->error("来源 {$source->id} 采集失败：{$exception->getMessage()}");
        }
    }

    $this->info("采集完成，共处理 {$sources->count()} 个来源，新增 {$createdCount} 条，失败 {$failedCount} 个。");

    return $failedCount > 0 ? 1 : 0;
})->purpose('采集所有启用来源的 RSS/Atom');

// 调度策略：固定每 10 分钟触发一次采集。
Schedule::command('news:fetch-enabled')->everyTenMinutes();
