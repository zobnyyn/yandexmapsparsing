FROM php:8.3-fpm-alpine

# Установка системных зависимостей
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    mysql-client

# Установка PHP расширений
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создание пользователя
RUN addgroup -g 1000 www && adduser -D -u 1000 -G www www

# Установка рабочей директории
WORKDIR /var/www

# Копирование файлов приложения
COPY --chown=www:www . /var/www

# Установка прав
RUN chown -R www:www /var/www

USER www

EXPOSE 9000
CMD ["php-fpm"]

