FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Activer Apache proprement
RUN a2dismod mpm_event || true
RUN a2dismod mpm_worker || true
RUN a2enmod mpm_prefork

RUN a2enmod rewrite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
