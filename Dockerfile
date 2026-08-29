FROM alpine/git:2.49.1 AS tcpdf

ARG TCPDF_REVISION=fbbaf14cfae8fe646f154f7c530d15ec25764040
RUN git clone --branch 6.11.4 --depth 1 https://github.com/tecnickcom/TCPDF.git /tcpdf \
    && test "$(git -C /tcpdf rev-parse HEAD)" = "${TCPDF_REVISION}" \
    && rm -rf /tcpdf/.git

FROM alpine/git:2.49.1 AS shlz-ui

ARG SHLZ_UI_REVISION=a0a8ca6df60b84aa1fe10a1cb500de32dacd4516
RUN git clone https://github.com/Antropophag/shlz-ui.git /shlz-ui \
    && git -C /shlz-ui checkout --detach "${SHLZ_UI_REVISION}" \
    && test "$(git -C /shlz-ui rev-parse HEAD)" = "${SHLZ_UI_REVISION}"

FROM node:22.22.0-alpine3.23 AS shlz-ui-build

WORKDIR /shlz-ui
COPY --from=shlz-ui /shlz-ui ./
RUN npm ci --no-audit --no-fund \
    && npm run generate \
    && npm run build:packages \
    && rm -rf .git node_modules

FROM php:8.5-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends socat \
    && docker-php-ext-install -j"$(nproc)" mysqli pcntl \
    && groupadd --gid 10001 fmonitor \
    && useradd --uid 10001 --gid 10001 --home-dir /home/fmonitor --create-home --shell /usr/sbin/nologin fmonitor \
    && mkdir -p /home/fmonitor/.local/state/fmonitor2 \
    && chown -R fmonitor:fmonitor /home/fmonitor \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /workspace/fmonitor-2

COPY --from=shlz-ui-build /shlz-ui /workspace/shlz-ui
COPY --from=tcpdf /tcpdf ./vendor/tecnickcom/tcpdf
COPY rapid-pilot/tcpdf-autoload.php ./vendor/autoload.php
COPY app ./app
COPY public ./public
COPY rapid-pilot ./rapid-pilot

RUN chmod +x rapid-pilot/docker-entrypoint.sh rapid-pilot/workforce-worker.sh \
    && php rapid-pilot/verify-visual-contract.php

USER fmonitor

EXPOSE 8092

ENTRYPOINT ["rapid-pilot/docker-entrypoint.sh"]
