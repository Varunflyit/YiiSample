# PHP Docker image for Yii 2.0 Framework runtime
# ==============================================

ARG PHP_BASE_IMAGE_VERSION
FROM php:${PHP_BASE_IMAGE_VERSION} as min

# Install required system packages for PHP extensions for Yii 2.0 Framework
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
        intl

# Environment settings
ENV PHP_USER_ID=33 \
    PATH=/app:/app/vendor/bin:/root/.composer/vendor/bin:$PATH \
    TERM=linux

# Add configuration files
COPY image-files/min/ /

# Enable mod_rewrite for images with apache
RUN if command -v a2enmod >/dev/null 2>&1; then \
        a2enmod rewrite headers \
    ;fi

# Install Yii framework bash autocompletion
RUN mkdir /etc/bash_completion.d && \
    curl -L https://raw.githubusercontent.com/yiisoft/yii2/master/contrib/completion/bash/yii \
         -o /etc/bash_completion.d/yii

# Application environment
WORKDIR /app

RUN chmod 755 \
        /usr/local/bin/docker-php-entrypoint


FROM min as dev

# Install system packages
RUN apt-get update && \
    apt-get -y install \
            unzip \
        --no-install-recommends && \
        apt-get clean && \
        rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install common system packages for PHP extensions recommended for Yii 2.0 Framework
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
        soap \
        zip \
        bcmath \
        exif \
        gd \
        opcache \
        pdo_mysql \
        pdo_pgsql \
        imagick \
        mongodb \
        xdebug

# Add configuration files
COPY image-files/dev/ /

# Disable xdebug by default (see PHP_ENABLE_XDEBUG)
RUN rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Add GITHUB_API_TOKEN support for composer
RUN chmod 755 \
        /usr/local/bin/docker-php-entrypoint \
        /usr/local/bin/composer

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- \
        --filename=composer.phar \
        --install-dir=/usr/local/bin && \
    composer clear-cache

# Environment settings
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PHP_ENABLE_XDEBUG=0