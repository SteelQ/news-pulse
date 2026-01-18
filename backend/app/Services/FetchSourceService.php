<?php

namespace App\Services;

use App\Models\Entry;
use App\Models\FetchRun;
use App\Models\Source;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class FetchSourceService
{
    public function __construct(private readonly FeedParser $parser) {}

    /**
     * @return array{created_count: int, warning: ?string}
     */
    public function fetch(Source $source): array
    {
        $startedAt = Carbon::now();

        try {
            $response = Http::timeout(10)->get($source->feed_url);
            if ($response->failed()) {
                throw new RuntimeException("Feed 请求失败，HTTP {$response->status()}");
            }

            // 解析 RSS/Atom，产出可落库的最小字段。
            $parsed = $this->parser->parse($response->body());

            $createdCount = 0;
            foreach ($parsed['items'] as $item) {
                $dedupeKey = $this->buildDedupeKey($source->id, $item['url']);

                // 通过 dedupe_key 去重，避免重复写入造成脏数据。
                $entry = Entry::query()->firstOrCreate(
                    ['dedupe_key' => $dedupeKey],
                    [
                        'source_id' => $source->id,
                        'title' => $item['title'],
                        'url' => $item['url'],
                        'published_at' => $item['published_at'],
                        'summary' => $item['summary'],
                        'content' => $item['content'],
                        'dedupe_key' => $dedupeKey,
                    ]
                );

                if ($entry->wasRecentlyCreated) {
                    $createdCount++;
                }
            }

            $source->forceFill([
                'last_fetched_at' => Carbon::now(),
            ])->save();

            $warning = null;
            if ($parsed['missing_published_at_count'] > 0) {
                // 缺失发布时间时记录降级策略，方便后续排查数据质量。
                $warning = "有 {$parsed['missing_published_at_count']} 条缺失发布时间，已使用当前时间";
            }

            FetchRun::query()->create([
                'source_id' => $source->id,
                'status' => 'success',
                'started_at' => $startedAt,
                'finished_at' => Carbon::now(),
                'error_message' => $warning,
            ]);

            return [
                'created_count' => $createdCount,
                'warning' => $warning,
            ];
        } catch (Throwable $exception) {
            FetchRun::query()->create([
                'source_id' => $source->id,
                'status' => 'failed',
                'started_at' => $startedAt,
                'finished_at' => Carbon::now(),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function buildDedupeKey(int $sourceId, string $url): string
    {
        // 去重键基于来源与链接生成，确保同来源同链接只落一次库。
        return sha1($sourceId.'|'.$url);
    }
}
