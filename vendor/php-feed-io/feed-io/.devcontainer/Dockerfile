FROM php:8.1-cli

# Arguments defined in devcontainer.json
ARG VARIANT=8.1
ARG USER_UID=1000
ARG USER_GID=$USER_UID

# Install packages and PHP extensions based on composer.json requirements
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y --no-install-recommends \
       git \
       curl \
       unzip \
       zip \
       libxml2-dev \
    && docker-php-ext-install -j$(nproc) \
       dom \
    && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# Install Xdebug for development
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Create a non-root user to use
RUN groupadd --gid $USER_GID vscode \
    && useradd --uid $USER_UID --gid $USER_GID -m vscode \
    && apt-get update \
    && apt-get install -y sudo \
    && echo vscode ALL=\(root\) NOPASSWD:ALL > /etc/sudoers.d/vscode \
    && chmod 0440 /etc/sudoers.d/vscode

# Set up Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Configure PHP with proper settings for development
COPY php.ini /usr/local/etc/php/conf.d/php-custom.ini

USER vscode