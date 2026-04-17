FROM php:8.2-apache

# Extensions PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Activer rewrite (utile pour routing)
RUN a2enmod rewrite

# Copier projet
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80