# DataBridge CRM v2 — Docker image
# Base: serversideup/php:8.3-fpm-nginx
# Multi-stage: production + dev target

# ─── Production stage ───────────────────────────────────────────────
FROM serversideup/php:8.3-fpm-nginx AS production

# Switch to root for installing extensions
USER root

# Install additional PHP extensions not in base image
# pdo_mysql, opcache, pcntl, sodium are already in serversideup base
RUN install-php-extensions \
    intl \
    bcmath

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock ./

# Install production dependencies
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --prefer-dist

# Copy the rest of the application
COPY . .

# Set proper permissions and switch back to www-data
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data

# ─── Development stage ──────────────────────────────────────────────
FROM serversideup/php:8.3-fpm-nginx AS dev

# Switch to root for installing extensions
USER root

# Install additional PHP extensions + xdebug for dev
RUN install-php-extensions \
    intl \
    bcmath \
    xdebug

# Switch back to default user
USER www-data

WORKDIR /var/www/html

# In dev we mount the code as a volume, no COPY needed
# Composer install happens via AUTORUN or manually
