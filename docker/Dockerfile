FROM php:8.3-apache

# -----------------------------------------------------------------------------
# Base system packages + MySQL client (needed for `mysql` CLI inside the web
# container, used by `make import` / `make fresh`). `git` and `unzip` are
# required by Composer to install the many `dev-master` BitBucket-hosted
# packages declared in master/composer.json and workspace/composer.json.
# -----------------------------------------------------------------------------
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    default-mysql-client \
    git \
    unzip \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# -----------------------------------------------------------------------------
# Composer 2.x - the botai repo ships no vendor/ directory, so `composer install`
# is the first thing a developer runs inside `make shell` for both `master/`
# and `workspace/`.
# -----------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

# -----------------------------------------------------------------------------
# Base PHP extensions. Covers the vast majority of PHP/MySQL web apps.
# -----------------------------------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mysqli \
    gd \
    zip \
    intl \
    mbstring \
    curl \
    xml \
    opcache

# -----------------------------------------------------------------------------
# Per-project overrides. Populated via Compose build args (see
# docker-compose.yml -> services.web.build.args and docker/.env).
#
#   EXTRA_APT_PACKAGES="libpq-dev libwebp-dev"
#   EXTRA_PHP_EXTENSIONS="pdo_pgsql redis imagick"
#
# Both default to empty strings, so projects that don't need anything extra
# get a no-op layer here.
# -----------------------------------------------------------------------------
ARG EXTRA_APT_PACKAGES=""
ARG EXTRA_PHP_EXTENSIONS=""

RUN if [ -n "$EXTRA_APT_PACKAGES" ]; then \
        apt-get update \
        && apt-get install -y --no-install-recommends $EXTRA_APT_PACKAGES \
        && rm -rf /var/lib/apt/lists/*; \
    fi \
 && if [ -n "$EXTRA_PHP_EXTENSIONS" ]; then \
        docker-php-ext-install -j$(nproc) $EXTRA_PHP_EXTENSIONS; \
    fi

# Enable Apache modules
RUN a2enmod ssl rewrite

# Recommended PHP.ini settings
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'upload_max_filesize=20M'; \
    echo 'post_max_size=20M'; \
    echo 'memory_limit=256M'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

WORKDIR /var/www/html
