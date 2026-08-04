FROM php:8.3-cli-alpine
RUN apk add --no-cache git unzip sqlite-dev && docker-php-ext-install pdo_sqlite
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction && cp .env.example .env && touch database/database.sqlite && php artisan key:generate && php artisan migrate --force
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
