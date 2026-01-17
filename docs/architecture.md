# 项目架构规划文档 (Architecture Design)

## 1. 项目定位与 V1 范围
*   **目标**：聚合各类网站 Blog 和 Changelog 内容的网页端展示工具。
*   **V1 范围**：
    *   仅限 Web 端访问。
    *   核心功能：内容采集、存储与展示。
    *   暂不包含：移动端原生应用、复杂的账号体系、社交功能等。

## 2. 技术栈与版本（基础设施层面）
*   **后端**：Laravel 11 / PHP 8.4
*   **数据库**：MySQL 8.0
*   **前端**：Vue 3 + Vite (JavaScript)
*   **基础工具**：Docker / Docker Compose
*   **Node 版本**：Node 20 LTS (开发环境建议)

## 3. 系统架构（概念层）
系统采用前后端分离架构，主要模块划分如下：
*   **采集器 (Fetcher)**：负责从目标网站抓取 Blog/Changelog 数据。
*   **存储层 (DB)**：使用 MySQL 存储结构化内容。
*   **API 服务 (Backend)**：基于 Laravel 提供 RESTful API。
*   **Web 前端 (Frontend)**：基于 Vue 3 构建的单页应用 (SPA)。

## 4. Docker 基础设施设计
通过 Docker Compose 编排以下服务：
*   **app**：运行 PHP-FPM 环境 (Laravel 后端)。
*   **web**：Nginx 服务器，负责请求分发。
*   **db**：MySQL 数据库容器。
*   **node**：前端开发与编译容器。
*   **scheduler**：(可选) 运行 Laravel 定时任务的容器。

### 端口规划 (示例)
*   Web 访问端口：`8080` (通过 Nginx 暴露)。
*   数据库端口：`3306` (内部通信，可选暴露至宿主机 `33060`)。

### 数据持久化
*   MySQL 数据将挂载到宿主机卷，确保数据不随容器销毁而丢失。

## 5. 项目目录结构规划
```text
/
├── /backend      # Laravel 项目根目录
├── /frontend     # Vue 3 项目根目录
├── /docker       # Dockerfile 及相关配置文件
├── /docs         # 项目文档
└── docker-compose.yml
```

## 6. 环境变量与配置项清单
### 后端 (.env)
*   `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_URL`
*   `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

### 前端
*   `VITE_API_BASE_URL`：后端 API 的基地址。

## 7. 开发/运行流程概览
1.  **启动环境**：执行 `docker-compose up -d` 启动所有基础服务。
2.  **后端开发**：在 `backend` 容器内运行 `composer install` 及 `php artisan` 命令。
3.  **前端开发**：在 `node` 容器内运行 `npm install` 及 `npm run dev`。
4.  **接口联调**：前端通过 API 地址访问后端接口。

## 8. 扩展预留
*   后续可引入 Redis 进行内容缓存与队列管理。
*   支持集成消息队列处理高并发采集任务。
*   预留移动端 API 适配接口。
