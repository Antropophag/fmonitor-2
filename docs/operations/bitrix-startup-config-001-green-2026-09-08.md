# BITRIX-STARTUP-CONFIG-001 — проверка реализации

Дата: 2026-09-08. Исходная база main: `1dd62c2e`.
Кандидат: `1e9cbb1ce407464f4c491c7c44f38498e623c6dd`.
Образ реального финального smoke: `sha256:f0f4d13e2b47bf2b73057e905408cb95a2d0125560435edc1f3a8d64be8ae2ca`.

## Результат

По уточнению владельца штатный `make up` запускает пилот и Bitrix вместе.
Legacy exporter, извлекающая настройки Make-зависимость, отдельный `up-bitrix`
и opt-in профиль worker удалены. Native synchronization CLI и scheduler не менялись.

## Focused checks

- `php tests/Deployment/bitrix_startup_config_001_test.php` — PASS.
- `php rapid-pilot/verify-deployment-contract.php` — PASS.
- `php tests/InstallationProcess/workforce_worker_cli_manual_pilot_test.php` — PASS.
- `python3 tests/Verification/verification_inventory_001_test.py` — 15/15 PASS;
  исходные frozen digests сохранены, новый member проверяется отдельно.
- `python3 tools/delivery/render-dependencies.py --check` — PASS.
- `make architecture-check` — PASS, 7 правил.
- `make lint` — exit 0; после узкой правки parser его синтаксис повторно
  проверен выполнением focused suite.
- `git diff --check` и OpenSpec strict — PASS.

## Реальный Docker smoke

Кандидат скопирован в отдельную временную директорию без соседнего `fmonitor`.
Проект Compose `fm2-issue42-smoke`, порт HTTP `18092`, собственные volumes.
В PATH вместо host PHP установлен launcher с exit 97; доступны Make, стандартные
утилиты и Docker (включая его credential helper). В `.env` только синтетические
реквизиты, администратор в разрешённом домене `shlz.ru`.

Команда: `make --no-print-directory up`, с `COMPOSE_PROJECT_NAME=fm2-issue42-smoke`
и `COMPOSE_FILE=compose.yaml:smoke.override.yaml`. Override только меняет HTTP-порт
и передаёт тестовый CA штатному worker. HTTPS fixture — существующий
`tests/Support/bitrix_delivery_https_server.py`, сценарий `full`, сертификат
для `host.docker.internal`; ни один запрос не направляется в реальный Bitrix.

Фактически подтверждено:

1. `make up` проходит сборку и контейнерную подготовку, запускает три healthy
   сервиса. HTTP отвечает 302 на вход, worker `completed`, 2 страницы,
   `delivered=51`, `materialChanges=51`, `missing=0`.
2. После смены тестового токена повторный `make up` пересоздаёт worker;
   fixture получает новый токен. ID контейнера БД сохранён; повтор даёт
   `delivered=51`, `materialChanges=0`, checksum каталога прежний.
3. Без `.env`, с HTTP вместо HTTPS и с незакрытой кавычкой `make up`
   завершается ненулевым кодом, не вызывает Compose up, не выводит токен.
   Существующий secret побайтно сохранён, ID всех запущенных контейнеров прежние.
4. Вывод Make и Docker inspect пилота/worker не содержат тестовых токенов.
   Экспорт build context через временный `FROM scratch / COPY . /` доказал
   отсутствие `.env`, `.env.backup` и `.local`; `.env.example` остаётся.
   Поток `docker save` проверен на отсутствие marker-секретов во всех слоях.
5. Финальный образ с исправленным parser снова проходит реальный `make up`:
   три healthy сервиса, `completed`, 51 сотрудник, 0 материальных изменений.

Исходный smoke сначала остановился на ошибке тестовой подготовки: email
`example.invalid` не допускается существующим corporate-only bootstrap.
Исправлены только синтетические реквизиты. Диагностическая копия bootstrap удалена;
product/runtime исправлений вне #42 не потребовалось.

Логи и fixture-материалы остаются вне Git во временных каталогах.
Установленный `fmonitor2-manual` не менялся: оба контейнера healthy.

## Идентичность проверенных файлов

- `bin/fmonitor2-prepare-bitrix-config.php`: `25682fafd34ff526593f0f91a7c0d971bd282bd4b1cc4d1d8df3e7d7ffc01b29`
- `Makefile`: `4c0e3264cd9a96e74e48825876cff1d5d31cdfeed4c476a092c83b9be47839de`
- `compose.yaml`: `5441e12ee9cf3839f9fea709d39b957dbb8700c1c172f2bfb68b6d73fc041070`
- `.dockerignore`: `cd1914b3605fdff76b8423079e5e3e23220015148d8ea7a7ffb8e2dc70eb968c`
- `.gitignore`: `0f99624b50ae0e6c8f4ec891f9915a5fef2424160ca3af96b6d2a297283ba79e`
- `.env.example`: `df8606f5068a40df0b198223bca0f7fe377583fde94ecdc229910bc3b3cf5944`

## Review и CI

Gate1/Gate3, корректировка characterization и отдельный RED для JSON escape
зафиксированы в `reviews/tests/BITRIX-STARTUP-CONFIG-001*.md`.
Gate5 и полный CI фиксируются отдельно после завершения; этот отчёт не объявляет
production readiness или VERIFY_OK без соответствующего результата.
