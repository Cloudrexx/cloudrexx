FROM php:7.0-apache
RUN bash -c "apt update && apt install -y \
    # Ubuntu packages
        exiftool \
        libmemcached-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        libicu-dev \
        zlib1g-dev \
        ssmtp \
    # PHP extensions
        && pecl install memcached && echo extension=memcached.so >> /usr/local/etc/php/conf.d/memcached.ini \
        && docker-php-ext-configure exif && docker-php-ext-install exif && docker-php-ext-enable exif \
        && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && docker-php-ext-install -j$(nproc) gd \
        && docker-php-ext-install -j$(nproc) iconv mcrypt opcache mysqli pdo_mysql intl bcmath zip \
    # PHP configuration
        && echo 'date.timezone = \"UTC\"' >> /usr/local/etc/php/php.ini \
        && echo 'default_socket_timeout = 600' >> /usr/local/etc/php/php.ini \
        && echo 'sendmail_path = /usr/sbin/ssmtp -t' >> /usr/local/etc/php/php.ini \
    # Apache configuration
        && a2enmod rewrite \
        && a2enmod expires \
        && a2enmod headers \
        && echo \"UseCanonicalName On\" >> /etc/apache2/apache2.conf \
    # Cleanup
        && apt-get autoremove -y \
        && apt-get remove -y \
            libfreetype6-dev \
            libpng-dev \
            libmemcached-dev \
            libmcrypt-dev \
            libjpeg62-turbo-dev \
            libicu-dev \
            zlib1g-dev \
        && apt-get clean \
        && rm -rf /tmp/pear"
COPY clx-docker-ssmtp-config /etc/ssmtp/ssmtp.conf
COPY clx-docker-php-entrypoint /usr/local/bin/
ENTRYPOINT ["clx-docker-php-entrypoint"]
CMD ["apache2-foreground"]
