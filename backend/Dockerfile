FROM php:8.3-cli-alpine AS base

# Install system dependencies & PostgreSQL dev libraries
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    linux-headers

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    bcmath \
    opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist || true

# Copy application source code
COPY . .

# Finish composer autoload
RUN composer dump-autoload --optimize --no-dev || true

# Create required directories and set permissions
RUN mkdir -p var/log var/cache var/uploads var/temp \
    && chown -R www-data:www-data /var/www/html

EXPOSE 8000

USER www-data

CMD ["php", "-S", "0.0.0.0:8000", "public/index.php"]
