FROM php:8.2-apache

# Fix: disable conflicting MPMs, keep only prefork (required for PHP)
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true && \
    a2enmod mpm_prefork

COPY . /var/www/html/

RUN docker-php-ext-install mysqli

EXPOSE 80
