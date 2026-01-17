<?php

namespace Tests\Feature;

use App\Models\Entry;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_entries_with_pagination_and_default_order(): void
    {
        $source = Source::query()->create([
            'name' => '官方博客',
            'type' => 'blog',
            'feed_url' => 'https://example.com/blog.xml',
            'is_enabled' => true,
        ]);

        // 准备不同发布时间的数据，便于验证默认排序。
        Entry::query()->create([
            'source_id' => $source->id,
            'title' => '较早文章',
            'url' => 'https://example.com/old',
            'published_at' => now()->subDays(2),
            'summary' => '摘要',
            'content' => null,
            'dedupe_key' => 'dedupe-old',
        ]);
        Entry::query()->create([
            'source_id' => $source->id,
            'title' => '中间文章',
            'url' => 'https://example.com/middle',
            'published_at' => now()->subDay(),
            'summary' => '摘要',
            'content' => null,
            'dedupe_key' => 'dedupe-middle',
        ]);
        Entry::query()->create([
            'source_id' => $source->id,
            'title' => '最新文章',
            'url' => 'https://example.com/new',
            'published_at' => now(),
            'summary' => '摘要',
            'content' => null,
            'dedupe_key' => 'dedupe-new',
        ]);

        $response = $this->getJson('/api/entries?per_page=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'title',
                        'url',
                        'published_at',
                        'source' => [
                            'id',
                            'name',
                            'type',
                        ],
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ])
            ->assertJsonPath('data.0.title', '最新文章')
            ->assertJsonPath('data.1.title', '中间文章')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_can_filter_entries_by_source_and_title_keyword(): void
    {
        $primarySource = Source::query()->create([
            'name' => '主来源',
            'type' => 'blog',
            'feed_url' => 'https://example.com/main.xml',
            'is_enabled' => true,
        ]);
        $secondarySource = Source::query()->create([
            'name' => '次来源',
            'type' => 'changelog',
            'feed_url' => 'https://example.com/secondary.xml',
            'is_enabled' => true,
        ]);

        // 混合来源与标题，验证筛选条件同时生效。
        Entry::query()->create([
            'source_id' => $primarySource->id,
            'title' => 'Laravel 新版本发布',
            'url' => 'https://example.com/laravel-main',
            'published_at' => now(),
            'summary' => null,
            'content' => null,
            'dedupe_key' => 'dedupe-main-laravel',
        ]);
        Entry::query()->create([
            'source_id' => $primarySource->id,
            'title' => 'Vue 生态更新',
            'url' => 'https://example.com/vue-main',
            'published_at' => now()->subHour(),
            'summary' => null,
            'content' => null,
            'dedupe_key' => 'dedupe-main-vue',
        ]);
        Entry::query()->create([
            'source_id' => $secondarySource->id,
            'title' => 'Laravel 生态资讯',
            'url' => 'https://example.com/laravel-secondary',
            'published_at' => now()->subHours(2),
            'summary' => null,
            'content' => null,
            'dedupe_key' => 'dedupe-secondary-laravel',
        ]);

        $response = $this->getJson("/api/entries?source_id={$primarySource->id}&q=Laravel");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel 新版本发布')
            ->assertJsonPath('data.0.source.id', $primarySource->id);
    }
}
