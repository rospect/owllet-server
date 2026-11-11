FROM php:8.3-apache
RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite headers
COPY . /var/www/html/
RUN rm -f /var/www/html/db_credentials.php
# Max na size ng upload para sa photo ng receipt o kung anong image man yun
RUN printf "post_max_size=20M\nupload_max_filesize=20M\n" > /usr/local/etc/php/conf.d/owllet.ini
EXPOSE 80
