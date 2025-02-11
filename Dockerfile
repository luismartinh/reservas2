FROM php:7.4-apache

# Actualizar e instalar dependencias necesarias, incluyendo unzip y oniguruma
RUN apt-get -y update \
    && apt-get -y --no-install-recommends install \
        libfontconfig1 libxrender1 libxext6 zlib1g-dev libpng-dev libfreetype6-dev \
        libjpeg62-turbo-dev libxml2-dev git zip libzip-dev \
        libonig-dev unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP necesarias (incluyendo GD con soporte para FreeType)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli pdo pdo_mysql gd soap bcmath zip mbstring

# Instalar y configurar Xdebug y APCu
RUN pecl install xdebug-3.1.5 apcu \
    && docker-php-ext-enable xdebug apcu \
    && echo "\n\
xdebug.mode = debug \n\
xdebug.start_with_request = yes \n\
xdebug.client_port = 9003 \n\
xdebug.client_host = 172.18.0.1 \n\
xdebug.log = /var/log/xdebug.log \n\
xdebug.idekey = VSCODE \n\
" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Ajustar memoria PHP
RUN echo 'memory_limit = -1' > /usr/local/etc/php/conf.d/docker-php-memlimit.ini

# Copiar Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer
