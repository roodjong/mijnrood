FROM php:8.1-fpm as php_builder

RUN apt-get update && apt-get install -y zlib1g-dev g++ git libicu-dev zip libzip-dev libgd3 libgd-dev zip \
    && docker-php-ext-install intl opcache pdo pdo_mysql \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && docker-php-ext-configure zip \
    && docker-php-ext-configure gd \
    && docker-php-ext-install zip \
    && docker-php-ext-install gd

WORKDIR /var/www/project

ENV APP_ENV=prod

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY bin /var/www/project/bin
COPY config /var/www/project/config
COPY src /var/www/project/src
COPY composer.json composer.lock symfony.lock .env /var/www/project/
RUN mkdir /var/www/project/public
RUN composer install

RUN bin/console assets:install public

FROM node:18.8.0 as node_builder

COPY package.json yarn.lock ./
COPY --from=php_builder /var/www/project/vendor ./vendor
RUN yarn install
COPY assets ./assets
COPY webpack.config.js ./
RUN npm run build

FROM php:8.1-fpm as mijnrood_php

RUN apt-get update && apt-get install -y zlib1g-dev g++ git libicu-dev zip libzip-dev libgd3 libgd-dev zip \
    && docker-php-ext-install intl opcache pdo pdo_mysql \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && docker-php-ext-configure zip \
    && docker-php-ext-configure gd \
    && docker-php-ext-install zip \
    && docker-php-ext-install gd

WORKDIR /var/www/project

COPY --from=node_builder public /var/www/project/public
COPY --from=php_builder /var/www/project/public/bundles /var/www/project/public/bundles
COPY --from=php_builder --chown=www-data:www-data /var/www/project/var /var/www/project/var
COPY --from=php_builder /var/www/project/vendor /var/www/project/vendor

COPY bin /var/www/project/bin
COPY config /var/www/project/config
COPY migrations /var/www/project/migrations
COPY public/.htaccess /var/www/project/public/.htaccess
COPY public/index.php /var/www/project/public/index.php
COPY src /var/www/project/src
COPY templates /var/www/project/templates
COPY translations /var/www/project/translations
COPY .env composer.json /var/www/project/
COPY docker/prod/php-fpm-entrypoint.sh /var/www/project/
COPY docker/prod/php.ini /usr/local/etc/php/php.ini

CMD [ "/var/www/project/php-fpm-entrypoint.sh" ]

FROM nginx:1.23.1 as mijnrood_nginx

COPY --from=mijnrood_php /var/www/project/public /var/www/project/public
COPY docker/prod/nginx.conf /etc/nginx/nginx.conf
