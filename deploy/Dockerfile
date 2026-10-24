# iCodic — container image for Railway / Render / any Docker host (PHP 8.2 + Apache).
FROM php:8.2-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpng-dev libjpeg-dev libwebp-dev libzip-dev default-mysql-client \
 && docker-php-ext-configure gd --with-jpeg --with-webp && docker-php-ext-install -j"$(nproc)" pdo_mysql gd opcache \
 && a2enmod rewrite headers && apt-get clean
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public_html
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
 && printf 'ServerName localhost\nSetEnvIf X-Forwarded-Proto https HTTPS=on\n' > /etc/apache2/conf-available/icodic.conf && a2enconf icodic \
 && printf 'upload_max_filesize=8M\npost_max_size=10M\nexpose_php=Off\nsession.cookie_httponly=1\n' > /usr/local/etc/php/conf.d/icodic.ini
WORKDIR /var/www/html
COPY . /var/www/html
RUN mv public_html/install /tmp/install-disabled && mkdir -p storage/logs storage/sessions public_html/uploads && chown -R www-data:www-data storage public_html/uploads app/config \
 && chmod +x deploy/entrypoint.sh
EXPOSE 80
ENTRYPOINT ["/var/www/html/deploy/entrypoint.sh"]
