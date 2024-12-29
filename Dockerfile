FROM vaultke/php8-fpm-nginx
RUN apk --update add --no-cache php-sodium 
COPY . /var/www/html
WORKDIR /var/www/html
RUN chmod -R 777 /var/www/html
