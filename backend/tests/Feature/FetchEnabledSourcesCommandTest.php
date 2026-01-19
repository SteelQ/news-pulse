<?php

namespace Tests\Feature;

use App\Models\Entry;
use App\Models\FetchRun;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchEnabledSourcesCommandTest extends TestCase
{
    use RefreshDatabase;

    private function rssWithItem(string $title, string $link): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>示例 RSS</title>
        <item>
            <title>{$title}</title>
            <link>{$link}</link>
            <pubDate>Sat, 18 Jan 2026 08:00:00 GMT</pubDate>
            <description>摘要</description>
        </item>
    </channel>
</rss>
XML;
    }

    public function test_fetch_enabled_sources_command_continues_on_failure(): void
    {
        Carbon::setTestNow('2026-01-18 10:00:00');

        $successSource = Source::query()->create([
            'name' => '可用来源',
            'type' => 'blog',
            'feed_url' => 'https://example.com/ok.xml',
            'is_enabled' => true,
        ]);
        $failureSource = Source::query()->create([
            'name' => '故障来源',
            'type' => 'blog',
            'feed_url' => 'https://example.com/error.xml',
            'is_enabled' => true,
        ]);
        $disabledSource = Source::query()->create([
            'name' => '禁用来源',
            'type' => 'blog',
            'feed_url' => 'https://example.com/disabled.xml',
            'is_enabled' => false,
        ]);

        Http::preventStrayRequests();
        Http::fake([
            $successSource->feed_url => Http::response(
                $this->rssWithItem('第一条', 'https://example.com/first'),
                200
            ),
            $failureSource->feed_url => Http::response('服务不可用', 500),
        ]);

        $this->artisan('news:fetch-enabled-sources')
            ->assertExitCode(1);

        $this->assertSame(1, Entry::query()->count());
        $this->assertSame(2, FetchRun::query()->count());
        $statuses = FetchRun::query()->pluck('status')->all();
        sort($statuses);
        $this->assertSame(['failed', 'success'], $statuses);

        // 禁用来源不应发起请求，避免浪费资源。
        Http::assertSentCount(2);
    }

    public function test_fetch_enabled_sources_command_succeeds_when_all_sources_ok(): void
    {
        Carbon::setTestNow('2026-01-18 10:00:00');

        $firstSource = Source::query()->create([
            'name' => '官方博客',
            'type' => 'blog',
            'feed_url' => 'https://example.com/rss-1.xml',
            'is_enabled' => true,
        ]);
        $secondSource = Source::query()->create([
            'name' => '产品更新',
            'type' => 'changelog',
            'feed_url' => 'https://example.com/rss-2.xml',
            'is_enabled' => true,
        ]);

        Http::preventStrayRequests();
        Http::fake([
            $firstSource->feed_url => Http::response(
                $this->rssWithItem('第一条', 'https://example.com/first'),
                200
            ),
            $secondSource->feed_url => Http::response(
                $this->rssWithItem('第二条', 'https://example.com/second'),
                200
            ),
        ]);

        $this->artisan('news:fetch-enabled-sources')
            ->assertExitCode(0);

        $this->assertSame(2, Entry::query()->count());
        $this->assertSame(2, FetchRun::query()->count());
        $this->assertSame(2, FetchRun::query()->where('status', 'success')->count());
        $this->assertSame(2, Source::query()->whereNotNull('last_fetched_at')->count());

        // 所有启用来源都应被采集。
        Http::assertSentCount(2);
        $this->assertSame(
            0,
            Source::query()->where('is_enabled', false)->whereNotNull('last_fetched_at')->count()
        );
    }
}
