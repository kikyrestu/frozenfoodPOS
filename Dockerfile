FROM php:8.2-apache

# Instal ekstensi PHP yang dibutuhkan (misal: pdo_mysql)
RUN docker-php-ext-install pdo pdo_mysql

# Aktifkan mod_rewrite Apache supaya Htaccess jalan
RUN a2enmod rewrite

# Setup Working Directory
WORKDIR /var/www/html

# Ganti ownership folder supaya PHP punya akses tulis (misal untuk folder 'uploads')
RUN chown -R www-data:www-data /var/www/html
