FROM php:8.2-apache

# 1. Instalar dependencias para PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# 2. Habilitar mod_rewrite para que el Router de PHP funcione
RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
# 3. Configurar el directorio de trabajo
WORKDIR /var/www/html

# 4. Copiar el código al contenedor
COPY . .

# 5. Dar permisos a la carpeta (importante en Linux para subir archivos)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80