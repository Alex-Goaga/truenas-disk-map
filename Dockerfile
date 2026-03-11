FROM php:8.2-apache

RUN apt update && apt install -y sudo smartmontools sg3-utils lsscsi udev bash curl

COPY sas3ircu /usr/local/bin/sas3ircu
RUN chmod +x /usr/local/bin/sas3ircu

# Setam max_execution_time la 180s
RUN echo "max_execution_time = 180" > /usr/local/etc/php/conf.d/custom.ini

COPY . /var/www/html/
WORKDIR /var/www/html/
RUN rm -f /var/www/html/index.html
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

RUN echo "www-data ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers

EXPOSE 80
CMD ["apachectl", "-D", "FOREGROUND"]
