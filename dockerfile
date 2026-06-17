FROM php:8.5-apache

# 1. Instala dependências do Linux
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 2. Configura GD (com suporte a JPEG, WebP)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp

# 3. Instala as extensões (exif é a única que faltava para uploads)
RUN docker-php-ext-install gd mysqli pdo pdo_mysql zip exif

# 4. Ativa módulos do Apache
RUN a2enmod rewrite headers

# 5. Copia a configuração do OPcache (já ativa via php.ini)
COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# 6. Copia o código fonte
COPY . /var/www/html/

# 7. Permissões
RUN chown -R www-data:www-data /var/www/html