# PHP-FPM 8.4 镜像，用于运行 Laravel 11
FROM php:8.4-fpm

# 安装系统依赖和 PHP 扩展
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 设置工作目录
WORKDIR /var/www/html

# 设置权限（Laravel 需要 storage 和 bootstrap/cache 可写）
RUN chown -R www-data:www-data /var/www/html

# 暴露端口（PHP-FPM 通过 Unix socket 或 TCP 9000 端口）
EXPOSE 9000

CMD ["php-fpm"]
