# YII2-LOCAL-QUICKSTART-001 — канонический локальный запуск Yii2

## Простыми словами

После чистого клонирования пользователь заполняет один `.env` и выполняет `make up`; всё остальное проект делает сам и в конце выдаёт готовый Yii2-стенд. Повторный запуск сохраняет данные. Обычная остановка ничего не удаляет, а полное удаление возможно только отдельным явным `make reset`. Production deployment, Bitrix/jobs и импорт данных в этот срез не входят.

## Actor and public seam

- Actor: локальный разработчик или владелец тестового стенда.
- Owner decision: GitHub issue #128, 2026-09-14.
- Source oracle: принятый Yii2 clean runtime PR #127 и `docs/operations/production-runtime-runbook.md`.
- Public seam: `make up`, `make down`, `make logs`, `make ps`, `make reset`; configuration input `.env` по `.env.example`.

## Preconditions and input

- Чистый checkout reviewed source внутри пользовательского каталога.
- Доступны Git, Make, Docker daemon и Compose v2.
- `.env` — regular file, созданный из `.env.example`, с уникальной local Compose identity, портом, DB/runtime credentials, Yii keys длиной не менее 32 bytes, trusted host/scheme и initial-owner email/password.
- Значения production credentials, Bitrix и jobs не требуются.

## Acceptance contract

### A1 — Clean convergence

`make up` MUST проверить вход без вывода secrets, собрать актуальный runtime image, поднять MariaDB, создать либо проверить exact DML-only runtime DB account, выполнить `prepare`, canonical Yii migrations и runtime check, создать первого owner только в clean identity state, затем запустить `php`/`web` и завершиться 0 лишь после readiness. Результат: `/health/live` и `/health/ready` успешны, напечатан local URL.

### A2 — Repeat convergence and history

Повторный `make up` с тем же project/config SHALL вернуть стенд в ready и MUST не удалять либо переинициализировать database, users, domain history, sessions или artifacts. Миграции/prepare применяют только недостающее; owner provisioning допускает exact replay. Partial/different identity state отклоняется fail-closed без privilege escalation.

### A3 — One lifecycle identity

`up/down/logs/ps/reset` MUST использовать один `deploy/runtime/compose.yaml` и project из validated `.env`. `down` SHALL останавливать canonical Yii2 services без удаления volumes. `logs`/`ps` SHALL наблюдать их, а не legacy pilot. Обычный путь MUST не запускать и не читать `rapid-pilot` manifests.

### A4 — Explicit bounded reset

Только `make reset` MAY удалить local containers/volumes. Перед удалением он MUST подтвердить валидную local project identity и SHALL ограничить effect ресурсами этого project. Отсутствующая, placeholder, production-like или неоднозначная identity отклоняется до удаления. Соседние projects/checkouts не затрагиваются.

### A5 — Operational separation

Production runbook, низкоуровневые Compose/CLI, backup/restore и jobs инструкции MUST сохраниться. Quickstart MUST не выполнять production import/deployment, не включать jobs/Bitrix автоматически, не удалять legacy history и не объявлять CI/deployment GREEN.

## Rejected cases and reasons

- Нет `.env`, обязательного значения или остался placeholder → `LOCAL_CONFIG_INVALID`, до persistent effects.
- Docker/Compose недоступны → `LOCAL_DOCKER_UNAVAILABLE`, без readiness claim.
- DB account имеет лишние либо отличающиеся grants → `LOCAL_DB_ACCOUNT_MISMATCH`, без автоматического расширения/сужения неизвестной учётной записи.
- Identity tables непусты не как exact replay → `IDENTITY_NOT_EMPTY`, без создания/повышения owner.
- Любой prepare/migration/runtime/health subprocess неуспешен → ненулевой exit; стенд не объявляется ready.
- `reset` не может доказать bounded local project → `LOCAL_RESET_TARGET_REJECTED`, zero deletion.

## Authorization, audit and evidence

Local owner, запустивший Make target, авторизует effects только выбранного local project; это не production authorization. Команды не принимают secrets через CLI arguments и не печатают их. Source revision, image identity, project и safe stage outcomes наблюдаемы; полные logs и credentials не коммитятся. История доменных фактов не изменяется startup orchestration сверх canonical migrations/bootstrap.

## Independently determined examples

1. Пустые volumes + email `owner.local@example.test` → первый `make up` создаёт ровно одного initial owner и ready web; второй `make up` оставляет число identities и их identifiers неизменными.
2. После `make down` прежний owner входит после нового `make up`; это доказывает сохранение volumes.
3. Projects `fm2-local-a` и `fm2-local-b` существуют одновременно; `make reset` с config A удаляет только ресурсы с Compose identity A, а B остаётся ready.
4. `.env` содержит `replace_with_a_strong_unique_password` → команда отклоняется до запуска DB и не выводит значение placeholder как secret diagnostic.
