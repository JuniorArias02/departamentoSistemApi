FROM php:8.2-cli

# Instalar extensiones necesarias (PDO, MySQL, GD, MBString, ZIP)
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libxpm-dev \
    libonig-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
        --with-xpm \
    && docker-php-ext-install pdo pdo_mysql gd mbstring zip

# Copiar composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar proyecto
WORKDIR /app
COPY . .

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader

# Exponer puerto (Railway usa $PORT)
EXPOSE 8080

# Arrancar servidor embebido
CMD php -S 0.0.0.0:8080 -t .
