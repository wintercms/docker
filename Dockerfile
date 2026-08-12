ARG FRANKENPHP_VERSION="1.12.7"
ARG PHP_VERSION="8.5.9"

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION}-trixie
LABEL org.opencontainers.image.title="Winter CMS"
LABEL org.opencontainers.image.description="Builds and deploys a Winter CMS project using Docker."
LABEL org.opencontainers.image.source=https://github.com/wintercms/docker

ARG WINTER_USER="winter"

RUN \
    # Install Microsoft packages key for SQL Server support
    curl -sSL -o /tmp/packages-microsoft-prod.deb https://packages.microsoft.com/config/debian/13/packages-microsoft-prod.deb \
    && dpkg -i /tmp/packages-microsoft-prod.deb \
    && rm /tmp/packages-microsoft-prod.deb \
    && mkdir -p /opt/microsoft/msodbcsql18/ \
    && touch /opt/microsoft/msodbcsql18/ACCEPT_EULA \
    && apt-get update \
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
    && apt-get update \
    && apt-get install -y \
    git \
    unzip \
    tar \
    wget \
    # Clean up
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Setup Winter user and download Winter
RUN \
    groupadd -g 10000 -r ${WINTER_USER} \
    && useradd -m -d /home/winter -u 10000 -g 10000 -s /bin/bash ${WINTER_USER} \
    # Add additional capability to bind to port 80 and 443
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
    # Give write access to /config/caddy and /data/caddy
    && chown -R ${WINTER_USER}:${WINTER_USER} /config/caddy /data/caddy \
    && mkdir /winter \
    && chown -R ${WINTER_USER}:${WINTER_USER} /winter

COPY entrypoint.sh /entrypoint.sh
COPY config/php.ini /usr/local/etc/php/conf.d/winter.ini

# Switch to user
USER ${WINTER_USER}

# Re-declare in this stage - args before the first FROM are only available to FROM
ARG WINTER_VERSION="v1.2.12"
RUN composer create-project \
  --no-progress \
  --no-interaction \
  --no-scripts \
  --no-dev \
  --require winter/storm:${WINTER_VERSION} \
  --require winter/wn-system-module:${WINTER_VERSION} \
  --require winter/wn-backend-module:${WINTER_VERSION} \
  --require winter/wn-cms-module:${WINTER_VERSION} \
  wintercms/winter /winter ${WINTER_VERSION}

# Install Node for Mix/Vite support
ARG NODE_VERSION="v24.19.0"
ENV BASH_ENV=/home/${WINTER_USER}/.bash_env
ENV XDG_CONFIG_HOME=/home/${WINTER_USER}/.config
ENV NVM_DIR=/home/${WINTER_USER}/.config/nvm
ENV NODE_BIN_PATH=${NVM_DIR}/versions/node/${NODE_VERSION}/bin

RUN touch "${BASH_ENV}" \
    && echo '. "${BASH_ENV}"' >> ~/.bashrc \
    && mkdir -p /home/${WINTER_USER}/.config/nvm \
    && wget -qO- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | PROFILE="${BASH_ENV}" bash \
    && [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" \
    && nvm install "${NODE_VERSION}"

ENV PATH="${NODE_BIN_PATH}:$PATH"

# Set up environment
WORKDIR /winter

ENV SERVER_NAME=":8000"
ENV SERVER_ROOT="/winter/public"
ENV APP_DEBUG="false"
ENV APP_URL="http://localhost:8000"
ENV DB_CONNECTION="sqlite"
ENV DB_DATABASE="/winter/storage/database.sqlite"
ENV ACTIVE_THEME="demo"
ENV BACKEND_URI="backend"
ENV ROUTES_CACHE="true"
ENV ASSET_CACHE="true"

CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
ENTRYPOINT ["/entrypoint.sh"]
