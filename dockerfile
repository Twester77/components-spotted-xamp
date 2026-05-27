FROM php:8.5-apache

# 1. Instala dependências do Linux (Imagens, Zip e utilitários úteis)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd mysqli pdo pdo_mysql zip

# 2. Ativa o módulo mod_rewrite do Apache (para URLs limpas no futuro)
RUN a2enmod rewrite

# 3. Copia os arquivos do seu GitHub para dentro do servidor
COPY . /var/www/html/

# 4. Garante as permissões de acesso corretas para o Apache
RUN chown -R www-data:www-data /var/www/html