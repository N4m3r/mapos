FROM php:8.3-fpm

# PHP extensions installer (resolve deps automatically)
COPY --from=ghcr.io/mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install only what MapOS needs
RUN install-php-extensions \
        bcmath \
        bz2 \
        calendar \
        exif \
        gd \
        gettext \
        imagick \
        intl \
        mysqli \
        opcache \
        pdo_mysql \
        redis \
        sockets \
        zip \
    && docker-php-source delete \
    && rm -rf /tmp/* /var/tmp/*

# Permission for PHP to write in volumes
RUN usermod -u 1000 www-data

# Install supervisor and cron
RUN apt-get update \
    && apt-get install -y --no-install-recommends supervisor cron \
    && rm -rf /var/lib/apt/lists/*

# Setup cron jobs
RUN touch /var/log/cron.log
COPY cron-jobs /etc/cron.d/cron-jobs
RUN chmod 0644 /etc/cron.d/cron-jobs \
    && crontab /etc/cron.d/cron-jobs

# Supervisor
RUN mkdir -p /var/log/supervisor
COPY ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf

CMD ["/usr/bin/supervisord"]
