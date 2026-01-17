<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use HasFactory;

    /**
     * 可批量赋值的字段。
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'feed_url',
        'is_enabled',
        'last_fetched_at',
    ];

    /**
     * 字段类型转换。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'last_fetched_at' => 'datetime',
        ];
    }

    /**
     * 来源对应的条目列表。
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * 来源对应的采集运行记录。
     */
    public function fetchRuns(): HasMany
    {
        return $this->hasMany(FetchRun::class);
    }
}
