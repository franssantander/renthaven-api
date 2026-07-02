FROM php:8.4-fpm

ARG USER
ARG APP_REPOSITORY_NAME
ARG UID=1001
ARG GID=1001

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Remap www-data to match host UID/GID
RUN groupmod -g ${GID} www-data && \
    usermod -u ${UID} -g ${GID} www-data

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

# Set default user for this image — applies to CMD *and* docker exec
USER www-data

COPY --chown=www-data:www-data .docker/entrypoint.sh /entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]