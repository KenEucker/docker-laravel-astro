FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
  git unzip libzip-dev findutils \
  && docker-php-ext-install pdo_mysql zip \
  && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html/app

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000
CMD ["/entrypoint.sh"]
