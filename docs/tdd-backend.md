# 后端 TDD 开发规则

本文档定义了 News Pulse 项目后端（Laravel）的测试驱动开发（TDD）工作流规范。

## 1. TDD 核心原则

### 1.1 Red-Green-Refactor 循环

所有功能开发必须遵循以下三步循环：

1. **Red（红）**：先编写失败的测试用例
   - 明确功能需求与边界条件
   - 测试用例应能准确描述预期行为
   - 运行测试，确认测试失败（红色）

2. **Green（绿）**：编写最小实现使测试通过
   - 仅实现满足测试用例的最简代码
   - 运行测试，确认测试通过（绿色）

3. **Refactor（重构）**：优化代码结构
   - 在测试通过的基础上重构代码
   - 保持测试始终通过
   - 提升代码可读性与可维护性

### 1.2 强制要求

- **禁止先写实现后补测试**：所有新功能必须先有测试用例
- **禁止跳过测试**：除非有明确的技术限制，否则所有功能必须有对应测试
- **测试必须可重复运行**：测试不应依赖外部状态或随机数据（除非使用种子数据）

## 2. 测试分类与组织

### 2.1 测试类型

Laravel 项目使用 PHPUnit，测试分为两类：

- **Unit Tests（单元测试）**：位于 `tests/Unit/`
  - 测试独立的类、方法或函数
  - 不依赖数据库、文件系统或外部服务
  - 运行速度快，适合测试业务逻辑

- **Feature Tests（功能测试）**：位于 `tests/Feature/`
  - 测试完整的 HTTP 请求-响应流程
  - 可包含数据库操作、中间件、路由等
  - 适合测试 API 端点、控制器行为

### 2.2 测试文件命名规范

- 测试类名：`{被测试类名}Test.php`
  - 示例：`UserServiceTest.php`、`ArticleControllerTest.php`
- 测试方法名：`test_{功能描述}`
  - 示例：`test_can_create_user()`、`test_returns_404_when_article_not_found()`
- 使用描述性的方法名，清晰表达测试意图

### 2.3 测试文件结构示例

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase; // 功能测试中常用，用于重置数据库

    /**
     * 测试创建文章功能
     */
    public function test_can_create_article(): void
    {
        // Arrange（准备）：设置测试数据
        $user = User::factory()->create();
        $articleData = [
            'title' => '测试文章',
            'content' => '这是测试内容',
        ];

        // Act（执行）：执行被测试的操作
        $response = $this->actingAs($user)
            ->postJson('/api/articles', $articleData);

        // Assert（断言）：验证结果
        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'title',
                'content',
                'created_at',
            ]);
    }
}
```

## 3. 开发工作流

### 3.1 开始新功能开发

1. **明确需求**：理解功能需求与验收标准
2. **编写测试**：在 `tests/Feature/` 或 `tests/Unit/` 中创建测试文件
3. **运行测试**：确认测试失败（Red）
4. **实现功能**：编写最小实现使测试通过（Green）
5. **重构优化**：在测试保护下重构代码（Refactor）
6. **提交代码**：确保所有测试通过后再提交

### 3.2 运行测试命令

#### 在 Docker 容器内运行（推荐）

```bash
# 进入后端容器
docker compose exec app bash

# 运行所有测试
php artisan test

# 运行特定测试文件
php artisan test tests/Feature/ArticleControllerTest.php

# 运行特定测试方法
php artisan test --filter test_can_create_article

# 运行单元测试套件
php artisan test --testsuite=Unit

# 运行功能测试套件
php artisan test --testsuite=Feature
```

#### 使用 Composer 脚本（如果已配置）

```bash
# 在容器内或本地环境
composer test
```

### 3.3 测试覆盖率（可选）

```bash
# 生成测试覆盖率报告（需要安装 Xdebug）
php artisan test --coverage

# 仅显示覆盖率摘要
php artisan test --coverage --min=80
```

## 4. 测试最佳实践

### 4.1 使用 Laravel 测试辅助功能

- **数据库测试**：使用 `RefreshDatabase` trait 自动重置数据库
- **认证测试**：使用 `actingAs()` 模拟已登录用户
- **HTTP 测试**：使用 `getJson()`、`postJson()` 等方法测试 API
- **工厂数据**：使用 Model Factories 生成测试数据

### 4.2 测试数据管理

```php
// 使用 Factory 创建测试数据
$user = User::factory()->create();
$article = Article::factory()->create(['user_id' => $user->id]);

// 使用 Factory 状态
$admin = User::factory()->admin()->create();

// 使用序列化数据（不保存到数据库）
$articleData = Article::factory()->make();
```

### 4.3 断言最佳实践

- 使用 Laravel 提供的断言方法：`assertStatus()`、`assertJson()`、`assertDatabaseHas()` 等
- 断言应具体明确，避免过于宽泛的检查
- 测试边界条件：空值、无效输入、权限检查等

### 4.4 测试隔离

- 每个测试应独立运行，不依赖其他测试的执行顺序
- 使用 `RefreshDatabase` 或 `DatabaseTransactions` 确保数据库状态隔离
- 避免使用全局变量或单例状态

## 5. CI/CD 集成

项目已配置 GitHub Actions 自动运行测试：

- **触发时机**：每次 `push` 和 `pull_request`
- **执行内容**：运行所有后端测试
- **失败处理**：测试失败将阻止合并

详见 [`.github/workflows/backend-tests.yml`](../.github/workflows/backend-tests.yml)

## 6. 常见问题

### Q: 测试运行太慢怎么办？

A: 
- 优先使用单元测试测试业务逻辑
- 功能测试中使用 `RefreshDatabase` 而非完整迁移
- 考虑使用 SQLite 内存数据库进行测试

### Q: 如何测试需要外部 API 的功能？

A: 
- 使用 Mock/Stub 模拟外部 API 响应
- 使用 Laravel 的 `Http::fake()` 方法
- 避免在测试中实际调用外部服务

### Q: 测试失败但功能正常？

A: 
- 检查测试环境配置（`.env.testing` 或 `phpunit.xml`）
- 确认测试数据准备正确
- 检查测试断言是否过于严格

## 7. 参考资源

- [Laravel 测试文档](https://laravel.com/docs/testing)
- [PHPUnit 文档](https://phpunit.de/documentation.html)
- [测试驱动开发：实战与模式解析](https://www.amazon.com/Test-Driven-Development-Kent-Beck/dp/0321146530)

---

**重要提醒**：TDD 不仅是技术实践，更是思维方式的转变。坚持先写测试，让测试驱动设计，能够显著提升代码质量与可维护性。
