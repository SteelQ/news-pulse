<?php

use App\Models\Source;
use App\Services\FetchSourceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Throwable;

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

Artisan::command('news:fetch-enabled-sources', function (FetchSourceService $fetcher) {
    $sources = Source::query()
        ->where('is_enabled', true)
        ->orderBy('id')
        ->get();

    if ($sources->isEmpty()) {
        $this->info('当前没有启用的来源，跳过采集。');

        return 0;
    }

    $successCount = 0;
    $failureCount = 0;

    foreach ($sources as $source) {
        try {
            $result = $fetcher->fetch($source);
            $successCount++;

            $message = "来源 {$source->id} 采集完成，新增 {$result['created_count']} 条。";
            if ($result['warning'] !== null) {
                $message .= " 注意：{$result['warning']}。";
            }

            $this->info($message);
        } catch (Throwable $exception) {
            $failureCount++;
            // 单源失败不影响其它来源，确保整体可继续执行。
            $this->error("来源 {$source->id} 采集失败：{$exception->getMessage()}");
        }
    }

    $totalCount = $sources->count();
    $this->info("批量采集完成，成功 {$successCount}/{$totalCount}。");

    return $failureCount > 0 ? 1 : 0;
})->purpose('采集所有启用来源的 RSS/Atom');

// V1 固定每 10 分钟执行一次，后续可基于来源级别间隔优化。
Schedule::command('news:fetch-enabled-sources')
    ->everyTenMinutes();
