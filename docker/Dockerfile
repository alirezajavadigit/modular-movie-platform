FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS \
    postgresql-dev \
    supervisor

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    gd \
    intl \
    mbstring \
    bcmath \
    opcache \
    pcntl \
    exif

RUN pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini        /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/opcache.ini    /usr/local/etc/php/conf.d/99-opcache.ini

COPY docker/php/supervisord.conf /etc/supervisord.conf

WORKDIR /var/www/html

COPY . .

RUN composer install \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage \
    /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]