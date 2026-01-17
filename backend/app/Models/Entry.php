<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entry extends Model
{
    use HasFactory;

    /**
     * 可批量赋值的字段。
     *
     * @var list<string>
     */
    protected $fillable = [
        'source_id',
        'title',
        'url',
        'published_at',
        'summary',
        'content',
        'dedupe_key',
    ];

    /**
     * 字段类型转换。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * 条目所属来源。
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
