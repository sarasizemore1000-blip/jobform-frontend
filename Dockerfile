FROM php:8.2-apache

WORKDIR /var/www/html

COPY . /var/www/html/

RUN mkdir -p uploads && chmod -R 777 uploads

EXPOSE 80
