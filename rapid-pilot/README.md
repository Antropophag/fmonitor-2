# FMonitor 2.0 rapid pilot

This directory contains the fast functional pilot and its locally scoped working instructions.

See `AGENTS.md` before making changes here. The repository's existing product documents and early implementation are the design and domain foundation, while work contained in this directory is intentionally developed through rapid browser-driven iterations without the repository-wide SSD + TDD ceremony.

For a non-Docker local launch, install PHP dependencies with `composer install --no-dev`. New assignment orders are rendered as one PDF containing the order and its appendix; already prepared HTML artifacts remain downloadable as immutable history.

## Запуск в Docker из WSL

После клонирования репозитория из WSL достаточно выполнить в корне проекта:

```bash
make up
```

При первом запуске Makefile извлекает конфигурацию read-only Bitrix API из
соседнего legacy-репозитория `../fmonitor` в git-ignored файл `.local/` с
правами `0600`. Секреты не попадают ни в образ, ни в этот репозиторий.

Пилот будет доступен на <http://127.0.0.1:8092/>. `make down` останавливает
контейнеры с сохранением данных, `make logs` показывает логи, а `make reset`
удаляет локальную базу и состояние пилота.

Поддерживаются как Docker Engine, установленный непосредственно внутри WSL,
так и Docker Desktop с включённой WSL integration.

Локальный Composer для этого сценария не нужен: зафиксированная версия TCPDF
загружается Docker-сборкой напрямую из исходного GitHub-репозитория.

Отдельный контейнер выполняет реальную синхронизацию монтажников при старте и
затем каждый час. Каждый запуск сохраняется, а первое появление, первое
наблюдение увольнения и последующие изменения сотрудников ведутся append-only.
Данные MariaDB и история сохраняются между `make down` / `make up`.

## Актуальная сборка

После штатного bootstrap локальной базы обновите read-only production-снимок карточек:

```bash
FMONITOR_PILOT_ACTIVE_MANIFEST="$HOME/.local/state/fmonitor2/pilot-demo/78d99d34/active.json" \
FMONITOR_SOURCE_USER='<read-only user>' \
FMONITOR_SOURCE_PASSWORD='<read-only password>' \
php rapid-pilot/import-production-object-details.php
```

Запуск собранной rapid-pilot оболочки:

```bash
php rapid-pilot/start.php
```

Карточка объекта дополняет действующий процессный интерфейс структурированным локальным снимком production-представления «Адресный перечень». Production остаётся read-only; снимок хранится только в активной локальной pilot-генерации.

## Локальная авторизация

Каталог пользователей и роли переносятся из legacy FMonitor командой `import-production-users.php`. Вход разрешён активным пользователям с корпоративным email `@shlz.ru`. Если email ещё отсутствует в локальном каталоге, при создании пароля система добавляет учётную запись без процессных ролей; роли не выдаются саморегистрацией и по-прежнему поступают из legacy. Исходные legacy-пароли не импортируются. Пароль хранится только как Argon2id-хэш (bcrypt используется как fallback, если Argon2id недоступен в сборке PHP).
