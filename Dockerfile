FROM dunglas/frankenphp:1.9-php8.4-trixie
LABEL org.opencontainers.image.title="Winter CMS Docker Image - PHP 8.4 with FrankenPHP"
LABEL org.opencontainers.image.description="Builds and deploys a Winter CMS project using Docker."
LABEL org.opencontainers.image.source=https://github.com/wintercms/docker

ARG USER=winter
ARG WINTER_TAG=v1.2.8

RUN \
    # Install Microsoft packages key for SQL Server support
    curl -sSL -o /tmp/packages-microsoft-prod.deb https://packages.microsoft.com/config/debian/13/packages-microsoft-prod.deb \
    && dpkg -i /tmp/packages-microsoft-prod.deb \
    && rm /tmp/packages-microsoft-prod.deb \
    && mkdir -p /opt/microsoft/msodbcsql18/ \
    && touch /opt/microsoft/msodbcsql18/ACCEPT_EULA \
    && apt update \
    # Install PHP extensions
    && install-php-extensions \
        gd \
        intl \
        memcached \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlsrv \
        pdo_odbc \
        redis \
        zip \
    # Install additional software for Composer
    && apt update \
    && apt install -y \
        git \
        unzip \
        tar \
        wget \
    # Clean up
    && apt clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Setup Winter user and download Winter
RUN \
    groupadd -g 10000 -r ${USER} \
    && useradd -m -d /home/winter -u 10000 -g 10000 -s /bin/bash ${USER} \
    # Add additional capability to bind to port 80 and 443
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
    # Give write access to /config/caddy and /data/caddy
    && chown -R ${USER}:${USER} /config/caddy /data/caddy \
    && mkdir /winter \
    && cd /winter \
    && composer create-project --no-progress --no-interaction wintercms/winter /winter "${WINTER_TAG}" \
    && chown -R ${USER}:${USER} /winter

WORKDIR /winter
USER winter

ENV SERVER_NAME=":8000"
ENV SERVER_ROOT="/winter/public"
ENV APP_DEBUG="false"
ENV APP_URL="http://localhost:8000"
ENV ACTIVE_THEME="demo"
ENV BACKEND_URI="backend"
ENV ROUTES_CACHE="true"
ENV ASSET_CACHE="true"

COPY entrypoint.sh /entrypoint.sh
COPY config/php.ini /usr/local/etc/php/conf.d/winter.ini

CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
ENTRYPOINT ["/entrypoint.sh"]
