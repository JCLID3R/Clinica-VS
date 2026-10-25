# Imagen para la nube (Render, Railway u otro servicio con Docker).
# Un solo servidor Apache sirve:
#   /          -> frontend (HTML, CSS, JS)
#   /api/v1/*  -> backend PHP (backend/public/index.php)
FROM php:8.3-apache

# Extensión de PostgreSQL para PDO y módulo de reescritura de Apache
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev \
 && docker-php-ext-install pdo_pgsql \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

COPY despliegue/apache.conf /etc/apache2/sites-available/000-default.conf
COPY despliegue/php.ini     /usr/local/etc/php/conf.d/clinica.ini
COPY despliegue/iniciar.sh  /usr/local/bin/iniciar.sh

COPY frontend/ /var/www/html/
COPY backend/  /var/www/backend/
RUN rm -f /var/www/backend/.env && chmod +x /usr/local/bin/iniciar.sh

# La configuración (base de datos, entorno) llega por variables de entorno del servicio
ENV APP_ENTORNO=produccion PORT=80
EXPOSE 80
CMD ["iniciar.sh"]
