FROM php:8.3-apache
RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite headers
COPY . /var/www/html/
# Huwag isama ang lokal na credentials sa cloud; env vars ang gamit.
RUN rm -f /var/www/html/db_credentials.php
# Max na laki ng upload para sa photo ng resibo
RUN printf "post_max_size=20M\nupload_max_filesize=20M\n" > /usr/local/etc/php/conf.d/owllet.ini
EXPOSE 80
