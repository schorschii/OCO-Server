FROM php:apache
WORKDIR /var/www/oco

# variables
ENV WEBAPP_ROOT /var/www/oco
ENV APACHE_DOCUMENT_ROOT ${WEBAPP_ROOT}/frontend

# set up apache
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf && \
    a2enmod rewrite

# install necessary PHP extensions
RUN apt-get update && apt-get install -y libzip-dev libldap2-dev && \
    docker-php-ext-install pdo_mysql zip ldap

# copy web app files
COPY . ${WEBAPP_ROOT}
RUN if [ ! -f "$WEBAPP_ROOT/conf.php" ]; then echo 'config.php missing - please create it from config.php.example!'; exit 1; fi

# set up cron jobs
RUN apt-get install -y cron && \
    echo "*/2 *  * * *  cd $WEBAPP_ROOT && /usr/local/bin/php console.php housekeeping >>/var/log/oco-cron.log 2>&1" > /etc/cron.d/oco && \
    echo "*/10 *  * * *  cd $WEBAPP_ROOT && /usr/local/bin/php console.php ldapsync >>/var/log/oco-cron.log 2>&1" >> /etc/cron.d/oco && \
    chmod 0644 /etc/cron.d/oco && crontab /etc/cron.d/oco

# start cron and apache
CMD cron && apache2-foreground
