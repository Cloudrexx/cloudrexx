FROM orsolin/docker-php-5.3-apache
RUN bash -c "apt update && apt install -y \
    # Ubuntu packages
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng12-dev \
        libicu-dev \
        ssmtp \
    # Fixes
        && mkdir /usr/include/freetype2/freetype \
        && ln -s /usr/include/freetype2/freetype.h /usr/include/freetype2/freetype/freetype.h \
    # PHP extensions
        && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && docker-php-ext-install gd \
        && docker-php-ext-install iconv mysqli pdo_mysql \
        && pear config-set php_ini /etc/php5/apache2/php.ini \
        && printf \"\n\" | pecl install apc \
    # PHP configuration
        && echo 'date.timezone = \"UTC\"' >> /etc/php5/apache2/php.ini \
        && echo 'default_socket_timeout = 600' >> /etc/php5/apache2/php.ini \
        && echo 'sendmail_path = /usr/sbin/ssmtp -t' >> /etc/php5/apache2/php.ini \
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
            libjpeg62-turbo-dev \
            libicu-dev \
        && apt-get clean \
        && rm -rf /tmp/pear"
COPY clx-docker-ssmtp-config /etc/ssmtp/ssmtp.conf
