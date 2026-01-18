<?php

use App\Models\Source;
use App\Services\FetchSourceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
