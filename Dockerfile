FROM php:8.2-apache

# 1. Capture the ARG from docker-compose
ARG USER
ARG UID=1000

# 2. Create the system user 'francis'
RUN useradd -G sudo,www-data -u $UID -d /home/$USER -m $USER

# Enable mod_rewrite
RUN a2enmod rewrite

# Install dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev zlib1g-dev libicu-dev unzip && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# 3. FIX: COPY paths (No more "renthaven-api/" prefix)
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader

# 4. FIX: Copy everything from the current context
COPY . .

# Run composer autoload
RUN composer dump-autoload

# 5. Set Permissions so both 'francis' and Apache (www-data) can write
RUN chown -R $USER:www-data /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Ensure correct Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# 6. Switch to your user for terminal commands
USER $USER

EXPOSE 80