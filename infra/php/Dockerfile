FROM node:24-bookworm-slim@sha256:55fb5f5a527ea2c5c4a95f963e48ef90aa38c7b346054fdc8ee9957a2b55d205 AS node
FROM php:8.2.35-apache-bookworm@sha256:5714c8607608e50d880a5f222fab556edecd4a11b9c50c81fce1c7932c1cf604
RUN apt-get update && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install -j2 mysqli pdo_mysql opcache curl \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers \
    && a2dissite 000-default \
    && sed -i 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf
COPY --from=node /usr/local/bin/node /usr/local/bin/node
WORKDIR /opt/zinema
# .dockerignore contains only individually reviewed source paths.
COPY . /opt/zinema/
COPY infra/php/site.conf /etc/apache2/sites-enabled/zinema.conf
COPY infra/php/local.ini /usr/local/etc/php/conf.d/zinema-local.ini
RUN mkdir -p /runtime/sessions /tmp/apache2 \
    && chown -R www-data:www-data /runtime /tmp/apache2 \
    && chmod 0755 /opt/zinema/infra/php/start.sh
ENV APACHE_RUN_DIR=/tmp/apache2 APACHE_PID_FILE=/tmp/apache2/apache.pid \
    APACHE_RUN_USER=www-data APACHE_RUN_GROUP=www-data PHP_BINARY=/usr/local/bin/php
USER www-data
EXPOSE 8080
ENTRYPOINT ["/opt/zinema/infra/php/start.sh"]
CMD ["apache2-foreground"]
