#start with our base image (the foundation) - version 7.1.5
FROM php:7.1.9-apache

#install all the system dependencies and enable PHP modules
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpq-dev \
    libmcrypt-dev \
    git \
    zip \
    unzip \
    && rm -r /var/lib/apt/lists/* \
    && docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd \
    && docker-php-ext-install \
    intl \
    mbstring \
    mcrypt \
    pcntl \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    zip \
    opcache

#install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

#set our application folder as an environment variable
ENV APP_HOME /var/www/html

#change uid and gid of apache to docker user uid/gid
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

#change the web_root to laravel /var/www/html/public folder
RUN sed -i -e "s/html/html\/public/g" /etc/apache2/sites-enabled/000-default.conf

# enable apache module rewrite
RUN a2enmod rewrite

RUN echo "makesure not using cache"

#copy source files and run composer
COPY . $APP_HOME

WORKDIR $APP_HOME

# install all PHP dependencies
RUN composer install --no-interaction


#change ownership of our applications
RUN chown -R www-data:www-data $APP_HOME


RUN apt-get -qq update && apt-get -qq -y --no-install-recommends install \
    ca-certificates \
    curl \
    python \
    python-pip

RUN mkdir /etc/supervisord \
    && mkdir /etc/supervisord/conf.d \
    && mkdir /var/log/supervisord \
    && pip install supervisor

COPY supervisord.conf /etc/supervisord/

CMD ["/usr/local/bin/supervisord", "-c", "/etc/supervisord/supervisord.conf"]
