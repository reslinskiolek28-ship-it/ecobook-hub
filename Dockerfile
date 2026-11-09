FROM php:8.2-apache

WORKDIR /var/www/html

COPY . /var/www/html

RUN mkdir -p /var/www/html/data && chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
