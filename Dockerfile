FROM php:8.2-apache

# 1. Enable Apache mod_rewrite for custom framework routing
RUN a2enmod rewrite

# 2. Tell Apache to stop ignoring .htaccess files
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# 3. Install the MySQL database driver for PHP
RUN docker-php-ext-install pdo_mysql

# 4. Copy your backend project files into the web server directory
COPY . /var/www/html

# 5. Point Apache to look inside the public/ folder for index.php
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf