FROM node:20-alpine AS frontend

WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
RUN npm ci && npm run build

FROM php:8.3-cli-alpine

RUN apk add --no-cache git unzip sqlite-dev \
    && docker-php-ext-install pdo_sqlite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && cp .env.example .env \
    && touch database/database.sqlite \
    && php artisan key:generate \
    && php artisan migrate --seed --force

EXPOSE 8000

# Render 會透過 PORT 環境變數指定公開服務的監聽埠；
# 本機 Docker 沒有提供 PORT 時則維持使用 8000。
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
