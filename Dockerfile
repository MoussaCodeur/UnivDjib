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
# Installer extensions PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Désactiver tous les MPM conflictuels
RUN a2dismod mpm_event || true
RUN a2dismod mpm_worker || true
RUN a2enmod mpm_prefork

RUN a2enmod rewrite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html
