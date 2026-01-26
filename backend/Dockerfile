# Stage 1: Build stage
FROM php:8.2-fpm-alpine AS builder

# Install the PHP extension installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install system dependencies and PHP extensions in one go (faster)
# Use BuildKit cache mount for apk
RUN --mount=type=cache,target=/var/cache/apk \
    apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    build-base \
    autoconf

RUN install-php-extensions \
    gd \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    zip \
    intl

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy only composer files first for better caching
COPY composer.json composer.lock ./

# Use BuildKit cache mount for composer
RUN --mount=type=cache,target=/tmp/cache \
    composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Stage 2: Production stage
FROM php:8.2-fpm-alpine

# Install runtime dependencies only
RUN --mount=type=cache,target=/var/cache/apk \
    apk add --no-cache \
    libpng \
    libzip \
    freetype \
    libjpeg-turbo \
    icu-libs \
    oniguruma \
    libavif \
    libwebp

WORKDIR /var/www

# Copy application code
COPY . .

# Copy vendor from builder stage
COPY --from=builder /var/www/vendor ./vendor
# Copy PHP extensions from builder stage
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Switch to non-root user
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
