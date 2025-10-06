FROM php:7.4-apache

RUN apt-get update && apt-get install -y \
        libonig-dev \
        libzip-dev \
        zip \
        unzip \
        curl \
        git \
        libxml2-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
    && docker-php-ext-install pdo_mysql mysqli mbstring zip exif pcntl gd opcache soap \
    && docker-php-ext-configure gd --with-freetype --with-jpeg

RUN a2enmod rewrite

WORKDIR /var/www/html
