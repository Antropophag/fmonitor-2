FROM composer:2.10.3@sha256:d8f6343d3fae98107426bc49163ccad46ef85aabd4a27d80a74401fab4aba332 AS composer

FROM alpine/git:2.49.1 AS shlz-ui
ARG SHLZ_UI_REVISION=9aaedf50eabf5f92e4af1cbc9c0f2a26a171b35b
RUN git clone https://github.com/Antropophag/shlz-ui.git /shlz-ui \
    && git -C /shlz-ui checkout --detach "${SHLZ_UI_REVISION}" \
    && test "$(git -C /shlz-ui rev-parse HEAD)" = "${SHLZ_UI_REVISION}"

FROM node:22.22.0-alpine3.23 AS shlz-ui-build
WORKDIR /shlz-ui
COPY --from=shlz-ui /shlz-ui ./
RUN npm ci --no-audit --no-fund \
    && npm run generate \
    && npm run build:packages

FROM php:8.4-fpm-bookworm@sha256:075b11566518bfa979bb9f2fe2e5359148326d659b15a2f414c2c305a0479a4e
RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx unzip \
    && docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql pcntl \
    && groupadd --gid 10001 fmonitor \
    && useradd --uid 10001 --gid 10001 --create-home --shell /usr/sbin/nologin fmonitor \
    && mkdir -p /var/lib/fmonitor/yii /tmp/nginx-client-body /tmp/nginx-fastcgi \
    && chown -R 10001:10001 /var/lib/fmonitor /tmp/nginx-* \
    && chmod 0700 /var/lib/fmonitor/yii \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /workspace/fmonitor-2
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
COPY app ./app
COPY --from=shlz-ui-build /shlz-ui/packages/styles/dist/shlz.css ./app/YiiRuntime/Assets/shlz.css
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress \
    && composer check-platform-reqs --no-dev
COPY config ./config
COPY public/yii.php ./public/yii.php
COPY bin/yii ./bin/yii
COPY deploy/yii2 ./deploy/yii2
COPY deploy/runtime/php-fpm.conf ./deploy/runtime/php-fpm.conf
COPY deploy/runtime/php.ini /usr/local/etc/php/conf.d/fmonitor.ini

ENV FMONITOR_YII_RUNTIME_PATH=/var/lib/fmonitor/yii
USER 10001:10001
EXPOSE 8080 9000
CMD ["php-fpm", "-F", "--fpm-config", "deploy/runtime/php-fpm.conf"]
