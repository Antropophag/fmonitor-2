# FMonitor 2.0 rapid pilot

This directory contains the fast functional pilot and its locally scoped working instructions.

See `AGENTS.md` before making changes here. The repository's existing product documents and early implementation are the design and domain foundation, while work contained in this directory is intentionally developed through rapid browser-driven iterations without the repository-wide SSD + TDD ceremony.

For a non-Docker local launch, install PHP dependencies with `composer install --no-dev`. New assignment orders are rendered as one PDF containing the order and its appendix; already prepared HTML artifacts remain downloadable as immutable history.

## Запуск в Docker из WSL

После клонирования репозитория из WSL достаточно выполнить в корне проекта:

```bash
make up
```

Обычный запуск не зависит от соседнего legacy-репозитория и не требует production-секретов.
Если нужна часовая синхронизация монтажников через Bitrix, добавьте
`FMONITOR_BITRIX_BASE_URL` и список `FMONITOR_BITRIX_DEPARTMENTS` через запятую
в git-ignored файл `.env`, затем выполните `make up-bitrix`. Команда сохраняет
runtime-конфигурацию в git-ignored `.local/` с правами `0600`. Для совместимости
при отсутствии этих переменных поддерживается извлечение конфигурации из
`../fmonitor`. Секреты не попадают ни в образ, ни в репозиторий.

Пилот будет доступен на <http://127.0.0.1:8092/>. `make down` останавливает
контейнеры с сохранением данных, `make logs` показывает логи, а `make reset`
удаляет локальную базу и состояние пилота.

Обычный bootstrap работает в `native-only` режиме и не создаёт тестовых
пользователей, объектов или процессов. Старые демонстрационные fixtures доступны
только для изолированных тестов через явный запуск
`docker compose run --rm -e FMONITOR_PILOT_FIXTURE_MODE=test-fixtures pilot`;
этот режим нельзя использовать
для operational-проверки владельцем продукта.

Поддерживаются как Docker Engine, установленный непосредственно внутри WSL,
так и Docker Desktop с включённой WSL integration.

## Первичная загрузка production

После `make up` скопируйте шаблон настроек, укажите реквизиты read-only
пользователя legacy MariaDB и выполните одну команду:

```bash
cp .env.example .env
# откройте .env в редакторе и замените FMONITOR_SOURCE_USER / FMONITOR_SOURCE_PASSWORD
make import-production
```

По умолчанию источник доступен контейнеру как `host.docker.internal:3306`, база —
`c1_fmonitor`, а cutoff — конец текущего дня. При необходимости переопределите
Файл `.env` исключён из Git. В нём можно переопределить
`FMONITOR_SOURCE_HOST`, `FMONITOR_SOURCE_PORT`, `FMONITOR_SOURCE_NAME` и
`FMONITOR_MIGRATION_CUTOFF='YYYY-MM-DD HH:MM:SS'`. Команда импортирует пользователей,
роли и все подходящие объекты, на которых работы ещё не начаты. Каталог монтажников
эта команда не читает из legacy: его авторитетным источником остаётся отдельная
синхронизация Bitrix. Значения из `.env` передаются контейнеру напрямую и не
разбираются Makefile, поэтому специальные символы в пароле сохраняются дословно.
Она предназначена для чистой native-only generation и останавливается при обнаружении
уже начатой инициализации, чтобы не смешать снимки разных моментов времени.

Локальный Composer для этого сценария не нужен: зафиксированная версия TCPDF
загружается Docker-сборкой напрямую из исходного GitHub-репозитория.

Отдельный контейнер выполняет реальную синхронизацию монтажников при старте и
затем каждый час. Каждый запуск сохраняется, а первое появление, первое
наблюдение увольнения и последующие изменения сотрудников ведутся append-only.
Данные MariaDB и история сохраняются между `make down` / `make up`.

## Актуальная сборка

После чистого `make reset && make up` штатная native-only инициализация запускается
одной командой из окружения, где уже заданы `FMONITOR_SOURCE_*`, `FMONITOR_DB_*`
и `FMONITOR_PILOT_ACTIVE_MANIFEST`:

```bash
php rapid-pilot/initialize-native-only.php --cutoff='2026-08-30 23:59:59'
```

Команда сначала доказывает пустую native-only generation, затем импортирует
production users, workforce, актуальный checklist template, только подтверждённые
`native_candidate / operational_case_import`, карточки объектов и связи template.
Production-чтения выполняются в READ ONLY transactions. Команда не вызывает
legacy active/history, historical replay или reconciliation и завершится ошибкой,
если итоговая generation содержит такие данные, fixtures либо synthetic ОТиЗ.

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
### Расчётные operands ОТиЗ

Плановая премия и Кшах вычисляются по приложению 4 к приказу №178 из
неизменяемого снимка характеристик карточки объекта: этажности,
грузоподъёмности и материала шахты. В первой версии управленческий коэффициент
КТУ равен `1,00`, а доля монтажника определяется подтверждённым фактическим
вкладом по назначениям выполненных пунктов чек-листа. Пользователь не вводит
эти значения вручную.
