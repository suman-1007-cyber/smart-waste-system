FROM php:8.2-apache

# Install MySQL extensions for PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy project files into the Apache web server directory
COPY . /var/www/html/

# Configure Apache document root, default landing page, and permissions
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite \
    && echo "DirectoryIndex register.php index.php" >> /etc/apache2/apache2.doc.conf