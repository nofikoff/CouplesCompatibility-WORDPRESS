FROM wordpress:latest

# Установка дополнительных инструментов для разработки
RUN apt-get update && apt-get install -y \
    vim \
    nano \
    wget \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Установка WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Включение режима отладки
RUN echo "define('WP_DEBUG', true);" >> /usr/src/wordpress/wp-config-docker.php \
    && echo "define('WP_DEBUG_LOG', true);" >> /usr/src/wordpress/wp-config-docker.php \
    && echo "define('WP_DEBUG_DISPLAY', true);" >> /usr/src/wordpress/wp-config-docker.php \
    && echo "define('SCRIPT_DEBUG', true);" >> /usr/src/wordpress/wp-config-docker.php

# Увеличение лимитов PHP для разработки
RUN echo "upload_max_filesize = 64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

# Включение отображения ошибок PHP
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/errors.ini

WORKDIR /var/www/html