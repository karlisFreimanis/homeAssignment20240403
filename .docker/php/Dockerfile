ARG TIMEZONE
ARG PHP_IMAGE

FROM ${PHP_IMAGE}

# Install dependencies
RUN apk update && apk add --no-cache \
    gnupg \
    g++ \
    procps \
    openssl \
    git \
    unzip \
    zlib-dev \
    libzip-dev \
    freetype-dev \
    libpng \
    libpng-dev \
    libjpeg-turbo-dev \
    icu-dev \
    oniguruma-dev \
    libxslt-dev \
    acl \
    curl \
#    postgresql-dev \
    mysql-dev

# Install Xdebug
RUN apk add --no-cache linux-headers $PHPIZE_DEPS \
    && pecl install xdebug-3.3.1 \
    && docker-php-ext-enable xdebug

RUN apk add --no-cache pcre-dev $PHPIZE_DEPS && \
    pecl update-channels && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apk del $PHPIZE_DEPS

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install pdo pdo_mysql zip xsl gd intl opcache exif mbstring # pdo_pgsql

# Set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', '${TIMEZONE}' > /usr/local/etc/php/conf.d/tzone.ini \
    && date

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/symfony
