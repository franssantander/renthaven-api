ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-fpm-alpine

ARG USER=francis
ARG UID=1001
ARG GID=1001

# ---- System dependencies ----
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    icu-dev \
    $PHPIZE_DEPS

# ---- PHP extensions Laravel needs ----
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# ---- Composer ----
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---- Non-root user matching your WSL host UID/GID ----
# Keeps files created inside the container (vendor/, storage/, bootstrap/cache)
# owned by you on the host instead of root.
RUN addgroup -g ${GID} ${USER} \
    && adduser -D -u ${UID} -G ${USER} ${USER}

WORKDIR /var/www/html

COPY .docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER ${USER}

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
