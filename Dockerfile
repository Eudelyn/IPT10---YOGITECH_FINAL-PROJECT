# Yogitech: PHP 8.3 + Apache. Replaces XAMPP's Apache/PHP.
FROM php:8.3-apache

# pdo_mysql for the database; curl, mbstring and fileinfo come with the image.
RUN docker-php-ext-install pdo_mysql \
 && a2enmod rewrite headers

# Serve public/ as the web root, so the site opens at http://localhost:8080/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

COPY docker/php.ini /usr/local/etc/php/conf.d/yogitech.ini
COPY docker/entrypoint.sh /usr/local/bin/yogitech-entrypoint
COPY docker/db-check.php /usr/local/bin/yogitech-db-check.php
RUN sed -i 's/\r$//' /usr/local/bin/yogitech-entrypoint && chmod +x /usr/local/bin/yogitech-entrypoint

WORKDIR /var/www/html
COPY . .
RUN mkdir -p storage/logs storage/cache storage/mail public/uploads \
 && chown -R www-data:www-data storage public/uploads

EXPOSE 80
ENTRYPOINT ["yogitech-entrypoint"]
CMD ["apache2-foreground"]
