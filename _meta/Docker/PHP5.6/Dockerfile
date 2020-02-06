FROM php:5.6-apache
RUN bash -c "apt update && apt install -y \
    # Ubuntu packages
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        libicu-dev \
        libzip-dev \
        ssmtp \
    # PHP extensions
        && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && docker-php-ext-install -j$(nproc) gd \
        && docker-php-ext-install -j$(nproc) iconv mcrypt mysql opcache mysqli pdo_mysql intl bcmath zip \
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
            libpng12-dev \
            libmcrypt-dev \
            libjpeg62-turbo-dev \
            libicu-dev \
            libzip-dev \
        && apt-get clean \
        && rm -rf /tmp/pear"
COPY clx-docker-ssmtp-config /etc/ssmtp/ssmtp.conf
COPY clx-docker-php-entrypoint /usr/local/bin/
ENTRYPOINT ["clx-docker-php-entrypoint"]
CMD ["apache2-foreground"]
