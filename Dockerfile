FROM php:8.2-cli

# Instalar extensiones necesarias (PDO, MySQL, etc.)
RUN docker-php-ext-install pdo pdo_mysql

# Copiar código de tu proyecto
WORKDIR /app
COPY . .

# Instalar dependencias con composer si usas dotenv o algo extra
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader
RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-gd

# Exponer puerto (Railway usa $PORT)
EXPOSE 8080

# Comando de arranque
CMD php -S 0.0.0.0:8080 -t .
