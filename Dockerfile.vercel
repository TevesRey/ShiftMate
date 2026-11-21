    FROM dunglas/frankenphp:1-php8.4-bookworm

RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    dom \
    xml \
    xmlwriter \
    curl \
    intl \
    zip \
    gd \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
COPY Caddyfile /etc/frankenphp/Caddyfile

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --optimize-autoloader

RUN mkdir -p bootstrap/cache \
    && mkdir -p /tmp/shiftmate-storage/app/private \
    /tmp/shiftmate-storage/app/public \
    /tmp/shiftmate-storage/framework/cache/data \
    /tmp/shiftmate-storage/framework/sessions \
    /tmp/shiftmate-storage/framework/views \
    /tmp/shiftmate-storage/logs \
    && chmod -R 0775 bootstrap/cache /tmp/shiftmate-storage

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LARAVEL_STORAGE_PATH=/tmp/shiftmate-storage

EXPOSE 80
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
