FROM php:8.5-apache

# 1. Instala dependências do Linux (incluindo AVIF)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libzip-dev \
    libavif-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-avif \
    && docker-php-ext-install gd mysqli pdo pdo_mysql zip exif opcache

# 2. Ativa o módulo mod_rewrite do Apache
RUN a2enmod rewrite

# 3. Copia a configuração do OPcache para dentro do container
COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# 4. Copia os arquivos do seu projeto para dentro do servidor
COPY . /var/www/html/

# 5. Garante as permissões de acesso corretas para o Apache
RUN chown -R www-data:www-data /var/www/html

# 6. (Opcional) Habilita o mod_headers para caching de assets
RUN a2enmod headers