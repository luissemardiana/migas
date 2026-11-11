FROM composer:2 AS dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install mbstring pdo_mysql \
    && a2enmod headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html
COPY --from=dependencies /app/vendor /var/www/html/vendor
RUN find /var/www/html/app /var/www/html/bin /var/www/html/config /var/www/html/database /var/www/html/public /var/www/html/resources /var/www/html/vendor -type d -exec chmod 755 {} + \
    && find /var/www/html/app /var/www/html/bin /var/www/html/config /var/www/html/database /var/www/html/public /var/www/html/resources /var/www/html/vendor -type f -exec chmod 644 {} +
RUN mkdir -p /var/www/html/storage/private /var/www/html/storage/sessions \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod 700 /var/www/html/storage/private /var/www/html/storage/sessions
COPY deploy/portal.ini /usr/local/etc/php/conf.d/portal.ini
COPY deploy/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/\r$//' /var/www/html/deploy/start.sh && chmod +x /var/www/html/deploy/start.sh

EXPOSE 80
CMD ["/var/www/html/deploy/start.sh"]
