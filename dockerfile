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

# 3. Instala as extensões (exceto opcache)
RUN docker-php-ext-install gd mysqli pdo pdo_mysql zip exif

# 4. Ativa o opcache (já vem com o PHP, só precisa habilitar)
RUN docker-php-ext-enable opcache

# 5. Ativa módulos do Apache
RUN a2enmod rewrite headers

# 6. Copia a configuração do OPcache
COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# 7. Copia o código fonte
COPY . /var/www/html/

# 8. Permissões
RUN chown -R www-data:www-data /var/www/html