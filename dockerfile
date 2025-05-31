FROM php:8.1-apache

# Habilita mysqli
RUN docker-php-ext-install mysqli

# Copia el contenido del proyecto
COPY . /var/www/html/

# Da permisos correctos
RUN chmod -R 755 /var/www/html && chown -R www-data:www-data /var/www/html

# Expón el puerto
EXPOSE 80
