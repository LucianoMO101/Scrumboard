FROM php:8.2-cli

RUN apt-get update && apt-get install -y git unzip libzip-dev zip && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo pdo_mysql
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

WORKDIR /app
COPY . /app

EXPOSE 80
CMD ["php", "-S", "0.0.0.0:80", "router.php"]
