FROM php:8.4-apache-trixie

# Set Timezone
ENV TZ="Europe/Berlin"

# Set the PHP configuration to development mode
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Set memory limit to unlimited in the php.ini
RUN sed -i 's/memory_limit = .*/memory_limit = -1/' $PHP_INI_DIR/php.ini

# Enable .htaccess files & mod_rewrite. Set the root Directory to /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride all/' /etc/apache2/apache2.conf
RUN a2enmod rewrite

# Install mysqli and pdo_mysql extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Most of this comes directly from the docker image
# We don't inherit from it becase it has a VOLUME that causes issues
RUN set -eux; \
	apt-get update; \
	apt-get install -y --no-install-recommends ghostscript git zip unzip less ssh-client mariadb-client libicu-dev;

# Install the intl extension
RUN docker-php-ext-configure intl \
	&& docker-php-ext-install intl

# Install Node.js
RUN curl -sL https://deb.nodesource.com/setup_24.x | bash - ; \
	apt-get install -y nodejs;

# Update npm to latest globally
RUN npm install -g npm@latest && npm cache clean --force

# Install additional PHP extensions
RUN set -ex; \
	apt-get update; \
	apt-get install -y --no-install-recommends \
	libfreetype6-dev \
	libjpeg-dev \
	libmagickwand-dev \
	libpng-dev \
	libzip-dev;

# Configure GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg;

# Install PHP extensions
RUN docker-php-ext-install -j "$(nproc)" \
	bcmath \
	exif \
	gd \
	mysqli \
	opcache \
	zip;

# Install imagick and xdebug
RUN pecl install imagick;
RUN pecl install xdebug;
RUN docker-php-ext-enable imagick xdebug;

# Install Redis
RUN pecl install redis && docker-php-ext-enable redis

# XDebug settings
RUN echo "[XDebug]\nxdebug.mode=debug\nxdebug.start_with_request=yes\nxdebug.client_port=9003\nxdebug.log_level=0" > $PHP_INI_DIR/conf.d/xdebug.ini

# Opcache recommended settings
RUN { \
	echo 'opcache.memory_consumption=128'; \
	echo 'opcache.interned_strings_buffer=8'; \
	echo 'opcache.max_accelerated_files=4000'; \
	echo 'opcache.revalidate_freq=2'; \
	echo 'opcache.fast_shutdown=1'; \
	} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Install Composer
WORKDIR /tmp
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
	&& php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer

# Add vscode user
RUN useradd -ms /bin/bash vscode \
	&& usermod -aG www-data vscode

# Install Laravel Installer via Composer
ENV COMPOSER_HOME=/composer
ENV PATH="$COMPOSER_HOME/vendor/bin:$PATH"
RUN mkdir -p $COMPOSER_HOME \
	&& composer global require laravel/installer

# Set working directory
WORKDIR /var/www/html
USER www-data

# Switch to root to set permissions
USER root

# Set permissions
RUN chown -R www-data:www-data /var/www/html/
RUN chmod g+w -R /var/www/html/
RUN find /var/www/html/ -type d -exec chmod g+s {} \;

# Clean up
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*