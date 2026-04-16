FROM php:8.2-apache

# Installer extensions PHP nécessaires
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Activer mod_rewrite (utile pour Laravel ou routing)
RUN a2enmod rewrite

# Copier ton projet dans le serveur Apache
COPY . /var/www/html/

# Donner les droits corrects
RUN chown -R www-data:www-data /var/www/html

# Port exposé
EXPOSE 80