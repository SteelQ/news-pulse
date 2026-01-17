<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FetchRun extends Model
{
    use HasFactory;

    /**
     * 可批量赋值的字段。
     *
     * @var list<string>
     */
    protected $fillable = [
        'source_id',
        'status',
        'started_at',
        'finished_at',
        'error_message',
    ];

    /**
     * 字段类型转换。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * 采集运行所属来源。
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
