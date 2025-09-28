FROM wordpress:latest

# Установка дополнительных инструментов для разработки
# Добавляем gpg и ca-certificates для работы с NodeSource и secure installs
RUN apt-get update && apt-get install -y \
    vim \
    nano \
    wget \
    unzip \
    git \
    gpg \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# ----------------------------------------------------------------------
## Установка Composer
# ----------------------------------------------------------------------
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ----------------------------------------------------------------------
## Установка Node.js (включает npm)
# Используем NodeSource для получения актуальной версии Node.js (например, 20.x LTS)
# ----------------------------------------------------------------------
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Проверка установки (опционально, можно убрать для уменьшения размера лога)
RUN node -v && npm -v && composer --version
# ----------------------------------------------------------------------

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