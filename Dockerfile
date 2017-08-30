FROM php:7.2-rc-cli

# unzip
RUN apt update && apt install -y unzip

# swoole
RUN pecl install swoole && docker-php-ext-enable swoole

# Выбираем рабочую папку
WORKDIR /root/app

# Открываем 80-й порт наружу
EXPOSE 80

# Копируем внутрь контейнера
COPY server.php /root/app

# Запускаем наш сервер
CMD php server.php
