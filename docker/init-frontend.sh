#!/bin/bash
# 前端初始化脚本 - 在容器内执行

set -e

echo "开始初始化 Vue 3 + Vite 前端..."

# 检查是否已存在 Vue 项目
if [ -f "package.json" ]; then
    echo "检测到已存在的前端项目，跳过初始化"
    exit 0
fi

# 使用 npm create 创建 Vue 3 项目（JavaScript 模式）
npm create vite@latest . -- --template vue --yes

# 安装依赖
npm install

echo "Vue 3 + Vite 前端初始化完成！"
