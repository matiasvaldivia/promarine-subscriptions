FROM php:8.3-fpm-alpine

RUN apk add --no-cache bash curl git icu-dev libzip-dev nodejs npm $PHPIZE_DEPS \
    && docker-php-ext-install bcmath intl pdo_mysql zip \
    && apk del $PHPIZE_DEPS
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
