FROM php:8.2-apache

# Install PostgreSQL PDO driver
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Increase PHP upload and memory limits inside the container
RUN echo "upload_max_filesize = 40M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads
RUN chmod -R 777 /var/www/html/uploads

EXPOSE 80
