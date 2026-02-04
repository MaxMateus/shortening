FROM php:8.2-fpm-alpine

# System deps
RUN apk add --no-cache \
    bash \
    git \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    libpng-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    zip \
    unzip

# PHP extensions required by Laravel + MySQL
RUN docker-php-ext-configure intl \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    bcmath \
    intl \
    zip \
    gd

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# Default command runs php-fpm
CMD ["php-fpm"]
