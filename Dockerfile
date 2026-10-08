# --- Stage 1: Frontend Build ---
FROM node:22-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# --- Stage 2: PHP Dependencies ---
FROM composer:latest AS php-builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --optimize-autoloader

# --- Stage 3: Final Runtime ---
FROM php:8.3-apache

# Install system dependencies for PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    libsqlite3-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions (including redis via pecl)
RUN docker-php-ext-install \
    pdo_mysql \
    pdo_sqlite \
    bcmath \
    gd \
    zip \
    intl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Configure Apache
# 1. Enable mod_rewrite for Laravel routing
RUN a2enmod rewrite

# 2. Set DocumentRoot to /var/www/html/public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Copy built assets from frontend-builder
COPY --from=frontend-builder /app/public/build ./public/build

# Copy composer dependencies from php-builder
COPY --from=php-builder /app/vendor ./vendor

# Set permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
