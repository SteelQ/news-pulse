<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Source;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SourceController extends Controller
{
    public function index(): JsonResponse
    {
        // 目前列表不做分页，先满足前端最小展示需求。
        $sources = Source::query()
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $sources,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // 基础校验：保证来源最小字段可用，避免空数据入库。
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'feed_url' => ['required', 'url', 'max:255'],
            'is_enabled' => ['sometimes', 'boolean'],
        ]);

        $source = Source::query()->create($validated);
        // 重新加载模型，补齐数据库默认字段（如 last_fetched_at）。
        $source->refresh();

        return response()->json([
            'data' => $source,
        ], 201);
    }

    public function update(Request $request, Source $source): JsonResponse
    {
        // 更新支持局部字段，避免前端必须提交全量数据。
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'max:50'],
            'feed_url' => ['sometimes', 'url', 'max:255'],
            'is_enabled' => ['sometimes', 'boolean'],
        ]);

        $source->fill($validated);
        $source->save();

        return response()->json([
            'data' => $source,
        ]);
    }

    public function destroy(Source $source): Response
    {
        // V1 先使用物理删除，后续如需审计再引入软删除。
        $source->delete();

        return response()->noContent();
    }
}
