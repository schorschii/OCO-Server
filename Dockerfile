FROM php:7.4-apache
WORKDIR /var/www/oco
ENV APACHE_DOCUMENT_ROOT /var/www/oco
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf && \
    a2enmod rewrite && \
    docker-php-ext-install pdo_mysql
COPY . /var/www/oco
