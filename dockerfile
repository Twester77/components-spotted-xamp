FROM php:8.2-apache
# Instala extensões necessárias para o MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql
# Copia os arquivos do seu GitHub para dentro do servidor
COPY . /var/www/html/
# Garante permissões de acesso
RUN chown -R www-data:www-data /var/www/html