# syntax=docker/dockerfile:1

FROM composer:2.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts
COPY . .
RUN composer dump-autoload --optimize

FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build

FROM php:8.2-apache
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    fonts-dejavu-core \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql zip bcmath gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN rm -f /var/www/html/public/hot \
    && mkdir -p /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/build \
    && chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]

EXPOSE 80
