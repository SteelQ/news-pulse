<?php

namespace Tests\Feature;

use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_sources(): void
    {
        Source::query()->create([
            'name' => '官方博客',
            'type' => 'blog',
            'feed_url' => 'https://example.com/blog.xml',
            'is_enabled' => true,
        ]);
        Source::query()->create([
            'name' => '变更日志',
            'type' => 'changelog',
            'feed_url' => 'https://example.com/changelog.xml',
            'is_enabled' => false,
        ]);

        $response = $this->getJson('/api/sources');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'type',
                        'feed_url',
                        'is_enabled',
                        'last_fetched_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_can_create_source_with_valid_payload(): void
    {
        $payload = [
            'name' => '产品更新',
            'type' => 'changelog',
            'feed_url' => 'https://example.com/product.xml',
            'is_enabled' => true,
        ];

        $response = $this->postJson('/api/sources', $payload);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'type',
                    'feed_url',
                    'is_enabled',
                    'last_fetched_at',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('sources', [
            'name' => $payload['name'],
            'type' => $payload['type'],
            'feed_url' => $payload['feed_url'],
            'is_enabled' => $payload['is_enabled'],
        ]);
    }

    public function test_create_source_requires_name_type_feed_url(): void
    {
        $response = $this->postJson('/api/sources', [
            'name' => '',
            'type' => '',
            'feed_url' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type', 'feed_url']);
    }

    public function test_can_update_source(): void
    {
        $source = Source::query()->create([
            'name' => '旧名称',
            'type' => 'blog',
            'feed_url' => 'https://example.com/old.xml',
            'is_enabled' => true,
        ]);

        $payload = [
            'name' => '新名称',
            'is_enabled' => false,
        ];

        $response = $this->patchJson("/api/sources/{$source->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('data.name', $payload['name'])
            ->assertJsonPath('data.is_enabled', $payload['is_enabled']);

        $this->assertDatabaseHas('sources', [
            'id' => $source->id,
            'name' => $payload['name'],
            'is_enabled' => $payload['is_enabled'],
        ]);
    }

    public function test_update_source_rejects_invalid_feed_url(): void
    {
        $source = Source::query()->create([
            'name' => '待更新来源',
            'type' => 'blog',
            'feed_url' => 'https://example.com/old.xml',
            'is_enabled' => true,
        ]);

        // 无效 URL 需要被拦截，避免脏数据进入系统。
        $response = $this->patchJson("/api/sources/{$source->id}", [
            'feed_url' => 'not-a-url',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['feed_url']);
    }

    public function test_can_delete_source(): void
    {
        $source = Source::query()->create([
            'name' => '待删除来源',
            'type' => 'blog',
            'feed_url' => 'https://example.com/delete.xml',
            'is_enabled' => true,
        ]);

        $response = $this->deleteJson("/api/sources/{$source->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('sources', [
            'id' => $source->id,
        ]);
    }
}
