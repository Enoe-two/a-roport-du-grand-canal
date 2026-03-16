FROM php:8.2-cli

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Copier le code
WORKDIR /app
COPY . .

# Exposer le port
EXPOSE 8080

# Lancer le serveur
CMD ["sh", "-c", "php -S 0.0.0.0:$PORT -t public"]