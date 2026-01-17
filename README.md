# News Pulse

一个聚合各类网站 Blog 和 Changelog 内容的小工具。

## 技术栈

- **后端**: Laravel 11 / PHP 8.4
- **前端**: Vue 3 + Vite (JavaScript)
- **数据库**: MySQL 8.0
- **容器化**: Docker / Docker Compose

## 快速开始

### 前置要求

- Docker 20.10+
- Docker Compose 2.0+

**注意**: 本项目采用纯 Docker 容器化开发，无需在宿主机安装 PHP、Node.js 或 MySQL。

### 启动步骤

1. **配置环境变量**

```bash
# 复制环境变量示例文件
cp .env.example .env  # 如果文件不存在
cp backend/.env.example backend/.env
```

2. **启动服务**

```bash
docker compose up -d
```

3. **初始化后端**

```bash
# 生成应用密钥
docker compose exec app php artisan key:generate

# 运行数据库迁移（如有）
docker compose exec app php artisan migrate
```

4. **访问服务**

- 后端 API: http://localhost:8080/api
- 前端开发服务器: http://localhost:5173

更多详细信息请查看 [开发环境设置指南](docs/dev-setup.md)。

## 项目文档

- [项目架构规划](docs/architecture.md)
- [开发环境设置指南](docs/dev-setup.md)
- [后端 TDD 开发规则](docs/tdd-backend.md)
- [项目开发进度追踪](docs/progress.md)

## 项目结构

```
news-pulse/
├── backend/          # Laravel 后端项目
├── frontend/         # Vue 3 + Vite 前端项目
├── docker/           # Docker 配置文件
└── docs/             # 项目文档
```

## 开发

详细开发工作流请参考 [开发环境设置指南](docs/dev-setup.md)。
