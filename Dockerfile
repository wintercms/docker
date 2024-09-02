# Based off Dockerfile from TrafeX/docker-php-nginx (https://github.com/TrafeX/docker-php-nginx).
# Credit to TrafeX for the original implementation.

FROM php:8.3.11-fpm-alpine3.20
LABEL org.opencontainers.image.title="Winter CMS Docker Image - PHP 8.3.11 / Alpine 3.20"
LABEL org.opencontainers.image.description="Builds and deploys a Winter CMS project using Docker."
LABEL org.opencontainers.image.source=https://github.com/wintercms/docker

# Install PHP extension script
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install PHP extensions
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions \
        gd \
        gettext \
        imap \
        intl \
        memcached \
        opcache \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlsrv \
        pdo_odbc \
        redis \
        zip

# Install other software
RUN apk add --no-cache \
    curl \
    git \
    nginx \
    supervisor \
    tar \
    unzip \
    zip


# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Add configuration
COPY config/nginx/nginx.conf /etc/nginx/nginx.conf
COPY config/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY config/php-fpm/php.ini /usr/local/etc/php/conf.d/docker-fpm.ini
COPY config/php-fpm/docker.conf /usr/local/etc/php-fpm.d/docker.conf
COPY config/php-fpm/pool.conf /usr/local/etc/php-fpm.d/www.conf
COPY config/supervisor/supervisor.conf /etc/supervisor/conf.d/supervisord.conf
RUN rm -f /usr/local/etc/php-fpm.d/zz-docker.conf

# Set the working directory
RUN mkdir -p /winter
WORKDIR /winter

# Make sure files/folders needed by the processes are accessable when they run under the nobody user
RUN chown -R nobody:nobody /winter /run /var/lib/nginx /var/log/nginx

# Switch to use a non-root user from here on
USER nobody

# Expose the port nginx is reachable on
EXPOSE 8080

# Let supervisord start nginx & php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Configure a healthcheck to validate that everything is up&running
HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping || exit 1