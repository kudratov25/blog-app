FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    sqlite-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    zip \
    libzip-dev \
    unzip \
    git \
    curl \
    $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis

RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pdo_sqlite \
        gd \
        zip \
        exif \
        bcmath \
        opcache \
        pcntl

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/api

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
