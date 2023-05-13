FROM php:8.1.7-fpm

RUN docker-php-ext-install pdo pdo_mysql

RUN apt-get update -y && apt-get install -y openssl zip unzip git libonig-dev librabbitmq-dev
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && ln -s /usr/local/bin/composer /usr/bin/composer

RUN docker-php-ext-install pdo mbstring sockets

RUN apt-get -y install --fix-missing \
    apt-utils \
    build-essential \
    git \
    curl \
    libcurl4 \
    libcurl4-openssl-dev \
    zlib1g-dev \
    libzip-dev \
    zip \
    libbz2-dev \
    locales \
    libmcrypt-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev

RUN echo "\e[1;33mInstall important docker dependencies\e[0m"
RUN docker-php-ext-install \
    exif \
    pcntl \
    bcmath \
    ctype \
    curl \
    iconv \
    xml \
    soap \
    pcntl \
    mbstring \
    bz2 \
    zip \
    intl

RUN pecl install mongodb && docker-php-ext-enable mongodb
RUN pecl install amqp && docker-php-ext-enable amqp

# Install Postgre PDO
RUN apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Install RabbitMQ
RUN apt-get update && \
    apt-get install -y rabbitmq-server && \
    rabbitmq-plugins enable rabbitmq_management && \
    service rabbitmq-server restart


WORKDIR /app
COPY ./ /app

# Wyczyszczenie konfiguracji Laravela
RUN php artisan config:clear
# Wyczyszczenie konfiguracji Laravela
RUN php artisan cache:clear

RUN composer install

CMD chmod -R 777 storage
CMD chmod -R 777 bootstrap/cache
CMD php artisan serve --host=0.0.0.0 --port=8000

