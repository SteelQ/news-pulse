# 开发环境设置指南

本文档说明如何在本地开发环境中启动和运行 News Pulse 项目。

## 前置要求

- Docker 20.10+ 
- Docker Compose 2.0+

**注意**：本项目采用纯 Docker 容器化开发，无需在宿主机安装 PHP、Node.js 或 MySQL。

## 快速开始

### 1. 环境变量配置

复制环境变量示例文件（如果不存在）：

```bash
# 项目根目录环境变量（Docker Compose 使用）
cp .env.example .env  # 如果文件不存在

# 后端环境变量
cp backend/.env.example backend/.env

# 前端环境变量（可选，Vite 会自动读取）
cp frontend/.env.example frontend/.env
```

根据需要修改 `.env` 文件中的配置项，特别是：
- `WEB_PORT`: Web 访问端口（默认 8080，如果被占用可改为 8081）
- `VITE_PORT`: 前端开发端口（默认 5173）
- `VITE_API_BASE_URL`: 前端 API 地址（默认 `/api`，Vite 会代理到 Nginx）
- `DB_PORT`: 数据库宿主机端口映射（默认 33060）
- `DB_DATABASE`: 数据库名
- `DB_USERNAME`: 数据库用户名
- `DB_PASSWORD`: 数据库密码
- `DB_ROOT_PASSWORD`: root 密码（仅用于初始化/管理）

### 2. 启动服务

```bash
# 构建并启动所有服务
docker compose up -d

# 查看服务状态
docker compose ps

# 查看日志
docker compose logs -f
```

### 3. 初始化后端

首次启动需要初始化 Laravel 应用：

```bash
# 生成应用密钥
docker compose exec app php artisan key:generate

# 运行数据库迁移（如果已有迁移文件）
docker compose exec app php artisan migrate

# 设置存储目录权限（容器内已自动处理，如遇问题可手动执行）
docker compose exec app chmod -R 775 storage bootstrap/cache
```

### 4. 访问服务

- **后端 API**: http://localhost:8080/api
- **前端开发服务器**: http://localhost:5173
- **数据库**: localhost:33060 (如已暴露)
  - 前端开发环境会将 `/api` 请求代理到后端

### 4.1 发布/生产模式（最小方案）

> 目标：单端口对外访问，前端由 Nginx 托管，API 走 `/api` 反代。

1. 构建前端产物（容器内执行）：

```bash
docker compose exec node npm run build
```

2. 构建产物默认输出到 `frontend/dist`，并通过 Nginx 挂载至 `/usr/share/nginx/html`。

3. 访问入口（单端口）：
   - **前端入口**: http://localhost:${WEB_PORT:-8080}
   - **后端 API**: http://localhost:${WEB_PORT:-8080}/api

4. 若更新前端产物，重新构建后重启 Nginx：

```bash
docker compose restart web
```

### 5. 联调快速验证（跑通端到端）

> 目标：让前端列表页能看到真实条目，并可进入详情页查看。

1. 进入前端「来源管理」页面，新增一个 RSS/Atom 来源（例如 Laravel News）。
2. 在来源列表中找到该来源的 **ID**。
3. 在终端执行采集命令：

```bash
docker compose exec app php artisan news:fetch-source <来源ID>
```

4. 打开「内容列表」页面，刷新即可看到最新条目，点击进入详情页验证展示。

## 开发工作流

### 后端开发（Laravel）

```bash
# 进入 app 容器
docker compose exec app bash

# 在容器内执行 Artisan 命令
docker compose exec app php artisan make:controller ExampleController
docker compose exec app php artisan make:model Example
docker compose exec app php artisan migrate

# 查看日志
docker compose logs -f app
```

### 前端开发（Vue 3 + Vite）

前端开发服务器在 `node` 容器内自动运行，支持热重载：

```bash
# 查看前端日志
docker compose logs -f node

# 如需手动重启前端服务
docker compose restart node
```

### 数据库操作

```bash
# 进入数据库容器
docker compose exec db mysql -u news_pulse -p news_pulse

# 或使用 root 用户
docker compose exec db mysql -u root -p

# 执行数据库迁移
docker compose exec app php artisan migrate

# 回滚迁移
docker compose exec app php artisan migrate:rollback
```

## 常用命令

```bash
# 停止所有服务
docker compose down

# 停止并删除数据卷（⚠️ 会删除数据库数据）
docker compose down -v

# 重启特定服务
docker compose restart app
docker compose restart web
docker compose restart node

# 查看服务资源使用情况
docker compose top

# 进入容器 shell
docker compose exec app bash
docker compose exec node sh
```

## 项目目录结构

```
news-pulse/
├── backend/          # Laravel 后端项目
│   ├── app/          # 应用核心代码
│   ├── config/       # 配置文件
│   ├── database/     # 数据库迁移和种子
│   ├── routes/       # 路由定义
│   └── .env          # 后端环境变量
├── frontend/         # Vue 3 + Vite 前端项目
│   ├── src/          # 源代码
│   ├── public/       # 静态资源
│   └── .env          # 前端环境变量
├── docker/           # Docker 配置文件
│   ├── nginx/        # Nginx 配置
│   ├── php/          # PHP 配置
│   ├── Dockerfile.php
│   └── Dockerfile.node
└── docker-compose.yml # Docker Compose 编排文件
```

## 故障排查

### 端口冲突

如果 8080 或 5173 端口被占用，修改 `.env` 文件中的端口配置即可，`VITE_API_BASE_URL` 默认 `/api` 无需随端口变更：

```bash
WEB_PORT=8081
VITE_PORT=5174
VITE_API_BASE_URL=/api
```

### 权限问题

如果遇到文件权限问题：

```bash
# 修复后端存储目录权限
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache
```

### 数据库连接失败

1. 检查数据库容器是否正常运行：`docker compose ps db`
2. 检查环境变量配置是否正确
3. 查看数据库日志：`docker compose logs db`

### 前端无法访问后端 API

1. 确认 `VITE_API_BASE_URL` 配置正确（默认 `/api`）
2. 检查 Nginx 配置是否正确代理到 PHP-FPM
3. 查看后端日志：`docker compose logs app`

## 测试

### 运行测试

项目采用 TDD（测试驱动开发）工作流，详细规则请参考 [后端 TDD 开发规则](tdd-backend.md)。

```bash
# 在容器内运行所有测试
docker compose exec app php artisan test

# 或使用 Composer 脚本
docker compose exec app composer test

# 运行特定测试文件
docker compose exec app php artisan test tests/Feature/ExampleTest.php
```

### CI/CD

项目已配置 GitHub Actions 自动运行测试，每次 push 或 pull request 都会触发测试流程。

## 下一步

- 查看 [架构设计文档](architecture.md) 了解系统架构
- 阅读 [后端 TDD 开发规则](tdd-backend.md) 了解开发规范
- 查看 [项目开发进度追踪](progress.md) 了解当前任务状态
- 开始开发你的第一个功能模块
