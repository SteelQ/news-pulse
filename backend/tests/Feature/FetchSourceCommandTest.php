<?php

namespace Tests\Feature;

use App\Models\Entry;
use App\Models\FetchRun;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchSourceCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_source_command_persists_entries_and_dedupes(): void
    {
        Carbon::setTestNow('2026-01-18 10:00:00');

        $source = Source::query()->create([
            'name' => '官方博客',
            'type' => 'blog',
            'feed_url' => 'https://example.com/rss.xml',
            'is_enabled' => true,
        ]);

        $rss = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>示例 RSS</title>
        <item>
            <title>第一条</title>
            <link>https://example.com/first</link>
            <pubDate>Sat, 18 Jan 2026 08:00:00 GMT</pubDate>
            <description>摘要一</description>
        </item>
    </channel>
</rss>
XML;

        Http::fake([
            $source->feed_url => Http::response($rss, 200),
        ]);

        $this->artisan('news:fetch-source', ['source_id' => $source->id])
            ->assertExitCode(0);
        $this->artisan('news:fetch-source', ['source_id' => $source->id])
            ->assertExitCode(0);

        $this->assertSame(1, Entry::query()->count());
        $this->assertSame(2, FetchRun::query()->count());
        $this->assertSame('success', FetchRun::query()->latest('id')->value('status'));
        $this->assertSame('https://example.com/first', Entry::query()->first()->url);

        $this->assertSame(
            Carbon::now()->toISOString(),
            $source->fresh()->last_fetched_at->toISOString()
        );
    }

    public function test_fetch_source_command_records_failure_on_http_error(): void
    {
        Carbon::setTestNow('2026-01-18 10:00:00');

        $source = Source::query()->create([
            'name' => '错误来源',
            'type' => 'blog',
            'feed_url' => 'https://example.com/error.xml',
            'is_enabled' => true,
        ]);

        Http::fake([
            $source->feed_url => Http::response('服务不可用', 500),
        ]);

        $this->artisan('news:fetch-source', ['source_id' => $source->id])
            ->assertExitCode(1);

        $this->assertSame(0, Entry::query()->count());
        $this->assertSame(1, FetchRun::query()->count());
        $this->assertSame('failed', FetchRun::query()->value('status'));
        $this->assertNotEmpty(FetchRun::query()->value('error_message'));
    }
}
