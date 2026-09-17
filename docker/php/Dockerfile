FROM php:8.2-fpm-alpine

# Extensoes necessarias: PDO para o MariaDB e curl para consumir a API do CREA-AM (RF02).
RUN apk add --no-cache curl-dev \
    && docker-php-ext-install pdo pdo_mysql curl

WORKDIR /var/www/html

# Copia o binario do Composer para dentro da imagem.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia o codigo-fonte da aplicacao para o container.
COPY . .

# Instala as dependencias de producao (sem pacotes de desenvolvimento/teste).
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Garante que o processo do PHP-FPM (www-data) possa escrever em storage/ e uploads/.
# So tem efeito em builds sem bind mount (ex: producao/CI) - o docker-compose.yml de
# desenvolvimento monta "./:/var/www/html" por cima disso, e o dono/permissao do host
# (Windows/WSL) prevalece sobre o que foi definido aqui na imagem.
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/public/uploads

# Reaplica a permissao em todo start do container (nao so no build), para cobrir o caso
# do bind mount de desenvolvimento sobrescrever o dono definido acima.
CMD ["sh", "-c", "chmod -R 777 /var/www/html/storage /var/www/html/public/uploads && exec php-fpm"]
