FROM php:8.3-apache

# Instala dependências do sistema e utilitários
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql mysqli zip

# Ativa o módulo mod_rewrite do Apache para rotas amigáveis (.htaccess)
RUN a2enmod rewrite

# Define o diretório de trabalho no contêiner
WORKDIR /var/www/html

# Ajusta permissões do diretório web
RUN chown -R www-data:www-data /var/www/html
