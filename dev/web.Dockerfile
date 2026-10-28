FROM php@sha256:1dcd13aa67697127a140a7d303ab955c923098c39d525ecd728f2e8afb4d1546
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev=1.6.48-1+deb13u6 libjpeg62-turbo-dev=1:2.1.5-4 libwebp-dev=1.5.0-0.1 libcurl4-openssl-dev=8.14.1-2+deb13u7 \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j2 gd mysqli pdo_mysql curl \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*
COPY *.php .htaccess favicon.ico /var/www/html/
COPY admin /var/www/html/admin
COPY api /var/www/html/api
COPY includes /var/www/html/includes
COPY assets /var/www/html/assets
COPY css /var/www/html/css
COPY js /var/www/html/js
COPY pages /var/www/html/pages
COPY dev /opt/zinema/dev
COPY database/migrations /opt/zinema/migrations
COPY dev/apache.conf /etc/apache2/sites-available/000-default.conf
COPY dev/php.ini /usr/local/etc/php/conf.d/zinema-dev.ini
RUN printf 'Listen 8088\n' > /etc/apache2/ports.conf \
    && chmod +x /opt/zinema/dev/entrypoint.sh
WORKDIR /var/www/html
ENTRYPOINT ["/opt/zinema/dev/entrypoint.sh"]
CMD ["apache2-foreground"]
