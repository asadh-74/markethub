# ---- Stage 1: build the Vue / Tailwind assets ----
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ---- Stage 2: PHP + Apache ----
FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip libzip-dev \
 && docker-php-ext-install zip \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
 && a2enconf laravel

WORKDIR /var/www/html
COPY . .
COPY --from=assets /app/public/build ./public/build

# Dev dependencies stay installed because the demo seeder uses model factories (Faker).
RUN composer install --optimize-autoloader --no-interaction --no-scripts \
 && chmod +x docker/start.sh

CMD ["docker/start.sh"]
