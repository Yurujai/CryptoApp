FROM php:7.3-fpm
WORKDIR "/application"

ARG LOCAL_UID
ARG LOCAL_GID

ARG PHP_MONGODB_VERSION=1.6.0

# Fix permission problems
RUN usermod -u ${LOCAL_UID:-33} www-data && groupmod -g ${LOCAL_GID:-33} www-data

# Install git, unzip
RUN apt-get update \
    && apt-get -y install git unzip vim

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# php-intl (as example of extension)
RUN apt-get update \
    && apt-get install -y libicu-dev \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* \
    && docker-php-ext-install intl

# Install zip (phpUnit requirement)
RUN apt-get update && \
    apt-get install -y \
    libzip-dev \
    && docker-php-ext-install zip

# Install mysql
RUN docker-php-ext-install mysqli

# install gd mbstring pdo
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libpq-dev \
 	&& rm -rf /var/lib/apt/lists/* \
 	&& docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
	&& docker-php-ext-install gd mbstring pdo pdo_mysql pdo_pgsql

RUN pecl install mongodb-${PHP_MONGODB_VERSION};
RUN pecl clear-cache ;
RUN docker-php-ext-enable mongodb;

# redis (as example of another extension from downloaded source code)
RUN curl -L -o /tmp/redis.tar.gz https://github.com/phpredis/phpredis/archive/3.1.6.tar.gz \
    && tar xfz /tmp/redis.tar.gz \
    && rm -r /tmp/redis.tar.gz \
    && mkdir -p /usr/src/php/ext/redis \
    && mv phpredis-3.1.6/* /usr/src/php/ext/redis/ \
    && docker-php-ext-install redis

RUN echo 'alias ll="ls -l"' >> ~/.bashrc
RUN echo 'alias sf="php bin/console"' >> ~/.bashrc
