FROM php:8.3-cli-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist || true

COPY . .

RUN composer dump-autoload --optimize --no-dev || true

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data /var/www/html

EXPOSE 3000

USER www-data

CMD ["php", "-S", "0.0.0.0:3000", "public/index.php"]
