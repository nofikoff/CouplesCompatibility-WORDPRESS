# ==============================================================================
# LOCAL DEVELOPMENT AND TESTING ONLY
# ==============================================================================
# This Docker configuration is used exclusively for local development and testing.
# For production, the project is designed for standard LAMP hosting with WordPress.
# ==============================================================================

FROM wordpress:latest

# Install additional development tools
# Add gpg and ca-certificates for NodeSource and secure installs
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
# Install Composer
# ----------------------------------------------------------------------
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ----------------------------------------------------------------------
# Install Node.js (includes npm)
# Using NodeSource to get the latest Node.js version (e.g., 20.x LTS)
# ----------------------------------------------------------------------
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Verify installation (optional, can be removed to reduce log output)
RUN node -v && npm -v && composer --version
# ----------------------------------------------------------------------

# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Enable debug mode
RUN echo "define('WP_DEBUG', true);" >> /usr/src/wordpress/wp-config-docker.php \
    && echo "define('WP_DEBUG_LOG', true);" >> /usr/src/wordpress/wp-config-docker.php \
    && echo "define('WP_DEBUG_DISPLAY', true);" >> /usr/src/wordpress/wp-config-docker.php \
    && echo "define('SCRIPT_DEBUG', true);" >> /usr/src/wordpress/wp-config-docker.php

# Increase PHP limits for development
RUN echo "upload_max_filesize = 64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

# Enable PHP error display
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/errors.ini

WORKDIR /var/www/html