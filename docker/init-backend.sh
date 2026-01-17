#!/bin/bash
# 后端初始化脚本 - 在容器内执行

set -e

echo "开始初始化 Laravel 后端..."

# 检查是否已存在 Laravel 项目
if [ -f "composer.json" ]; then
    echo "检测到已存在的 Laravel 项目，跳过初始化"
    exit 0
fi

# 使用 Composer 创建 Laravel 11 项目
composer create-project laravel/laravel:^11.0 . --prefer-dist --no-interaction

# 设置存储目录权限
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "Laravel 后端初始化完成！"
