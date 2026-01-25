# News Pulse 最小 API 接口文档（MVP）

> 目标：让前端不读源码也能对接。当前为 Markdown 版本，后续可升级为 OpenAPI。

## 基本约定

- Base URL：`/api`
- Content-Type：`application/json`
- 鉴权：V1 暂不启用鉴权
- 时间格式：统一为 ISO 8601 字符串（示例：`2026-01-25T08:30:00+00:00`）

## 通用响应结构

### 成功响应

- 列表接口：

```json
{
  "data": [],
  "meta": {}
}
```

- 单条详情/单资源接口：

```json
{
  "data": {}
}
```

### 常见错误响应

- 422：参数校验失败（常见于创建/更新）

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["错误描述"]
  }
}
```

- 404：资源不存在（例如 `/entries/{id}` 不存在）

```json
{
  "message": "Not Found"
}
```

> 注：具体错误 `message` 可能因框架默认行为略有差异，前端应以 HTTP 状态码为准。

## 来源（Source）

### 字段说明

| 字段名 | 类型 | 说明 | 备注 |
| --- | --- | --- | --- |
| id | number | 来源 ID | |
| name | string | 来源名称 | |
| type | string | 来源类型 | 例如 `blog`/`changelog` |
| feed_url | string | RSS/Atom 地址 | 需为合法 URL |
| is_enabled | boolean | 是否启用 | 默认 `true` |
| last_fetched_at | string \| null | 最近一次采集时间 | 未采集时为 `null` |
| created_at | string | 创建时间 | ISO 8601 |
| updated_at | string | 更新时间 | ISO 8601 |

### 获取来源列表

`GET /api/sources`

- 说明：当前不分页，按 `id` 升序返回

响应示例：

```json
{
  "data": [
    {
      "id": 1,
      "name": "官方博客",
      "type": "blog",
      "feed_url": "https://example.com/blog.xml",
      "is_enabled": true,
      "last_fetched_at": null,
      "created_at": "2026-01-25T08:00:00+00:00",
      "updated_at": "2026-01-25T08:00:00+00:00"
    }
  ]
}
```

### 新增来源

`POST /api/sources`

请求体字段：

| 字段名 | 必填 | 类型 | 说明 |
| --- | --- | --- | --- |
| name | 是 | string | 来源名称 |
| type | 是 | string | 来源类型 |
| feed_url | 是 | string | RSS/Atom 地址 |
| is_enabled | 否 | boolean | 是否启用 |

成功响应：`201 Created`，返回完整 `Source` 对象（同列表字段）。

### 更新来源

`PATCH /api/sources/{id}`

- 支持局部更新，仅传需要变更的字段
- 字段规则同“新增来源”

成功响应：`200 OK`，返回更新后的 `Source` 对象。

### 删除来源

`DELETE /api/sources/{id}`

- 说明：V1 使用物理删除
- 成功响应：`204 No Content`

## 条目（Entry）

### 列表条目字段说明

| 字段名 | 类型 | 说明 | 备注 |
| --- | --- | --- | --- |
| id | number | 条目 ID | |
| title | string | 条目标题 | |
| url | string | 原文链接 | |
| published_at | string | 发布时间 | ISO 8601 |
| source | object | 来源信息 | 仅含 `id/name/type` |

### 详情条目补充字段

| 字段名 | 类型 | 说明 | 备注 |
| --- | --- | --- | --- |
| summary | string \| null | 摘要 | 可为空 |
| content | string \| null | 正文内容 | 可为空 |

### 获取条目列表（分页/筛选）

`GET /api/entries`

查询参数：

| 参数 | 必填 | 说明 | 备注 |
| --- | --- | --- | --- |
| page | 否 | 页码 | 默认为 1 |
| per_page | 否 | 每页数量 | 默认 15，最小 1，最大 50 |
| source_id | 否 | 来源 ID | 按来源筛选 |
| q | 否 | 标题关键字 | 仅匹配 `title` |

响应示例：

```json
{
  "data": [
    {
      "id": 10,
      "title": "最新文章",
      "url": "https://example.com/new",
      "published_at": "2026-01-25T08:30:00+00:00",
      "source": {
        "id": 1,
        "name": "官方博客",
        "type": "blog"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

分页格式说明：

- `current_page`：当前页
- `per_page`：实际每页数量（会按 1~50 进行限制）
- `total`：总条目数
- `last_page`：最后一页页码

### 获取条目详情

`GET /api/entries/{id}`

响应示例：

```json
{
  "data": {
    "id": 10,
    "title": "详情文章",
    "url": "https://example.com/detail",
    "published_at": "2026-01-25T08:30:00+00:00",
    "summary": "摘要内容",
    "content": "正文内容",
    "source": {
      "id": 1,
      "name": "官方博客",
      "type": "blog"
    }
  }
}
```
