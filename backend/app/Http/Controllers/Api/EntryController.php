<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        if ($perPage < 1) {
            $perPage = 1;
        }
        // 控制分页上限，避免一次性拉取过多数据。
        if ($perPage > 50) {
            $perPage = 50;
        }

        $keyword = trim((string) $request->query('q', ''));

        $query = Entry::query()
            ->with(['source:id,name,type'])
            ->when($request->filled('source_id'), function ($query) use ($request) {
                // 按来源过滤，满足前端基础筛选需求。
                $query->where('source_id', $request->query('source_id'));
            })
            ->when($keyword !== '', function ($query) use ($keyword) {
                // 标题关键字匹配，先满足最小搜索能力。
                $query->where('title', 'like', '%' . $keyword . '%');
            })
            ->orderByDesc('published_at');

        $paginator = $query->paginate($perPage);

        $items = $paginator->getCollection()
            ->map(function (Entry $entry) {
                return [
                    'id' => $entry->id,
                    'title' => $entry->title,
                    'url' => $entry->url,
                    'published_at' => $entry->published_at?->toISOString(),
                    'source' => [
                        'id' => $entry->source?->id,
                        'name' => $entry->source?->name,
                        'type' => $entry->source?->type,
                    ],
                ];
            })
            ->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
