<?php

namespace Tests\Unit;

use App\Services\FeedParser;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class FeedParserTest extends TestCase
{
    public function test_can_parse_rss_items_and_fallback_published_at(): void
    {
        Carbon::setTestNow('2026-01-18 10:00:00');

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
        <item>
            <title>第二条</title>
            <link>https://example.com/second</link>
            <description>摘要二</description>
        </item>
    </channel>
</rss>
XML;

        $parser = new FeedParser;
        $result = $parser->parse($rss);

        $this->assertCount(2, $result['items']);
        $this->assertSame('第一条', $result['items'][0]['title']);
        $this->assertSame('https://example.com/first', $result['items'][0]['url']);
        $this->assertSame('第二条', $result['items'][1]['title']);
        $this->assertSame(
            Carbon::parse('2026-01-18 10:00:00')->toISOString(),
            $result['items'][1]['published_at']->toISOString()
        );
        $this->assertSame(1, $result['missing_published_at_count']);
    }

    public function test_can_parse_atom_items(): void
    {
        Carbon::setTestNow('2026-01-18 10:00:00');

        $atom = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>示例 Atom</title>
    <entry>
        <title>Atom 条目</title>
        <link href="https://example.com/atom" />
        <updated>2026-01-18T09:00:00Z</updated>
        <summary>Atom 摘要</summary>
    </entry>
</feed>
XML;

        $parser = new FeedParser;
        $result = $parser->parse($atom);

        $this->assertCount(1, $result['items']);
        $this->assertSame('Atom 条目', $result['items'][0]['title']);
        $this->assertSame('https://example.com/atom', $result['items'][0]['url']);
        $this->assertSame(
            Carbon::parse('2026-01-18T09:00:00Z')->toISOString(),
            $result['items'][0]['published_at']->toISOString()
        );
        $this->assertSame(0, $result['missing_published_at_count']);
    }
}
