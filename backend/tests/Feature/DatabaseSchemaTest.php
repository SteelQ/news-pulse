<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_sources_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('sources'));

        $this->assertTrue(Schema::hasColumns('sources', [
            'id',
            'name',
            'type',
            'feed_url',
            'is_enabled',
            'last_fetched_at',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_entries_table_has_expected_columns_and_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('entries'));

        $this->assertTrue(Schema::hasColumns('entries', [
            'id',
            'source_id',
            'title',
            'url',
            'published_at',
            'summary',
            'content',
            'dedupe_key',
            'created_at',
            'updated_at',
        ]));

        $indexNames = $this->getIndexNames('entries');

        $this->assertContains('entries_dedupe_key_unique', $indexNames);
        $this->assertContains('entries_published_at_index', $indexNames);
    }

    public function test_fetch_runs_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('fetch_runs'));

        $this->assertTrue(Schema::hasColumns('fetch_runs', [
            'id',
            'source_id',
            'status',
            'started_at',
            'finished_at',
            'error_message',
            'created_at',
            'updated_at',
        ]));
    }

    /**
     * 仅在 MySQL 下检查索引，避免不同驱动差异导致误报。
     *
     * @return array<int, string>
     */
    private function getIndexNames(string $table): array
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('索引检查仅在 MySQL 上执行。');
        }

        $indexes = DB::select("SHOW INDEX FROM {$table}");

        return array_values(array_unique(array_map(
            static fn ($index) => $index->Key_name,
            $indexes
        )));
    }
}
