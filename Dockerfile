# ==========================================
# STAGE 1: Build Frontend Assets (Vite & Tailwind)
# ==========================================
FROM node:20-alpine AS frontend_builder

WORKDIR /app

COPY package*.json ./
RUN npm ci || npm install

COPY resources/ resources/
COPY vite.config.js ./

RUN npm run build

# ==========================================
# STAGE 2: PHP-FPM Production Runtime
# ==========================================
FROM php:8.2-fpm-alpine AS production

# Install dependensi sistem dan library yang dibutuhkan ekstensi PHP
RUN apk add --no-cache \
    bash \
    curl \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    shadow \
    netcat-openbsd

# Konfigurasi & install ekstensi PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        opcache \
        pcntl \
        zip \
        gd \
        intl

# Ambil binary Composer dari official image
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Salin konfigurasi PHP & FPM khusus hemat RAM
COPY docker/php/php.ini /usr/local/etc/php/conf.d/sipeta.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-sipeta.conf

# Cache Composer dependensi (Layer Caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-progress --prefer-dist --optimize-autoloader

# Salin kode aplikasi (dikecualikan oleh .dockerignore)
COPY . .

# Salin hasil kompilasi frontend Vite dari Stage 1
COPY --from=frontend_builder /app/public/build ./public/build

# Finalisasi autoloader Composer
RUN composer dump-autoload --optimize --no-dev

# Siapkan entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Atur kepemilikan dan hak akses direktori storage & cache
RUN mkdir -p storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/views \
             storage/logs \
             bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

