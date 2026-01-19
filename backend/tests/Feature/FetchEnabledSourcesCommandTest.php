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

    public function test_fetch_enabled_sources_command_only_fetches_enabled_sources(): void
    {
        Carbon::setTestNow('2026-01-19 09:00:00');

        $enabledSource = Source::query()->create([
            'name' => '启用来源',
            'type' => 'blog',
            'feed_url' => 'https://example.com/enabled.xml',
            'is_enabled' => true,
        ]);

        $disabledSource = Source::query()->create([
            'name' => '禁用来源',
            'type' => 'blog',
            'feed_url' => 'https://example.com/disabled.xml',
            'is_enabled' => false,
        ]);

        $rss = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>示例 RSS</title>
        <item>
            <title>启用来源条目</title>
            <link>https://example.com/enabled/first</link>
            <pubDate>Mon, 19 Jan 2026 08:00:00 GMT</pubDate>
            <description>启用来源摘要</description>
        </item>
    </channel>
</rss>
XML;

        Http::fake([
            $enabledSource->feed_url => Http::response($rss, 200),
            $disabledSource->feed_url => Http::response($rss, 200),
        ]);

        $this->artisan('news:fetch-enabled')
            ->assertExitCode(0);

        $this->assertSame(1, Entry::query()->count());
        $this->assertSame($enabledSource->id, Entry::query()->value('source_id'));
        $this->assertSame(1, FetchRun::query()->count());
        $this->assertSame('success', FetchRun::query()->value('status'));
        Http::assertSentCount(1);
    }

    public function test_fetch_enabled_sources_command_continues_when_one_source_fails(): void
    {
        Carbon::setTestNow('2026-01-19 09:10:00');

        $failedSource = Source::query()->create([
            'name' => '异常来源',
            'type' => 'blog',
            'feed_url' => 'https://example.com/error.xml',
            'is_enabled' => true,
        ]);

        $successSource = Source::query()->create([
            'name' => '正常来源',
            'type' => 'blog',
            'feed_url' => 'https://example.com/good.xml',
            'is_enabled' => true,
        ]);

        $rss = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>示例 RSS</title>
        <item>
            <title>正常来源条目</title>
            <link>https://example.com/good/first</link>
            <pubDate>Mon, 19 Jan 2026 08:30:00 GMT</pubDate>
            <description>正常来源摘要</description>
        </item>
    </channel>
</rss>
XML;

        Http::fake([
            $failedSource->feed_url => Http::response('服务不可用', 500),
            $successSource->feed_url => Http::response($rss, 200),
        ]);

        $this->artisan('news:fetch-enabled')
            ->assertExitCode(1);

        $this->assertSame(1, Entry::query()->count());
        $this->assertSame(2, FetchRun::query()->count());
        $this->assertSame('failed', FetchRun::query()->where('source_id', $failedSource->id)->value('status'));
        $this->assertSame('success', FetchRun::query()->where('source_id', $successSource->id)->value('status'));
    }
}
