FROM php:8.2-apache

# Install MySQL extensions for PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy project files into the Apache web server directory
COPY . /var/www/html/

# Configure Apache document root and permissions
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite