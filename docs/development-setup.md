# Установка, запуск и проверки FMonitor 2.0

Эта инструкция описывает новый локальный checkout на Linux или macOS. Рабочий
пилот на `127.0.0.1:8092` и его volumes — отдельный контур: не останавливайте и
не сбрасывайте его ради подготовки второй машины или checkout. Для второго
одновременного контура задайте отдельные Compose project name, порты и volumes;
стандартные команды ниже используют `8092` для пилота и `23306` для test DB.

## Требования к машине

Нужны:

- Bash, Git, GNU Make, `rg` (ripgrep), `curl` и `tar`;
- PHP `8.5.x` с расширениями `mysqli`, `pcntl`, `dom`, `mbstring`, `curl`;
- Node.js `22.22.0` и его npm `10.9.4`;
- Python `3.12.11`;
- Docker Engine или Docker Desktop, запущенный daemon и Docker Compose v2;
- C compiler `cc` (обычно Clang или GCC), нужный npm-зависимостям с native addon.

На macOS удобно установить CLI-инструменты через Homebrew и Xcode Command Line
Tools; Docker Compose v2 входит в Docker Desktop. На Linux используйте пакеты
своего дистрибутива и официальный Docker repository. Точные инструкции:
[PHP](https://www.php.net/manual/ru/install.php),
[Node.js](https://nodejs.org/en/download),
[Python](https://www.python.org/downloads/),
[ripgrep](https://github.com/BurntSushi/ripgrep#installation),
[Docker](https://docs.docker.com/engine/install/) и
[Docker Desktop for Mac](https://docs.docker.com/desktop/setup/install/mac-install/).

Проверить машину без установки пакетов и без изменений файлов можно командой:

```bash
make doctor
```

Она проверяет версии и расширения, доступность Docker daemon/Compose, инструменты
сборки и, если dependency directories уже существуют, их целостность и готовность.
Исправьте каждую напечатанную ошибку и повторите команду.

## Закреплённые зависимости

Единый источник версий runtime и UI —
[`tools/delivery/dependencies.env`](../tools/delivery/dependencies.env).
PHP-пакеты закреплены [`composer.lock`](../composer.lock); среди них TCPDF.
Не подбирайте близкую версию вручную и не используйте произвольный HEAD
`shlz-ui`: setup проверяет точный commit из `dependencies.env`.

## Установка checkout

```bash
git clone https://github.com/Antropophag/fmonitor-2.git fmonitor-2
cd fmonitor-2
make setup
```

`make setup` устанавливает закреплённые PHP-зависимости, создаёт sibling checkout
`../shlz-ui` атомарно, устанавливает его npm-зависимости, формирует публичные
exports/packages, ставит закреплённый версией Playwright Chromium и может собрать
кэшируемые Docker images пилота и тестов. На Linux
Playwright использует `--with-deps` и может запросить `sudo` для системных
библиотек. Системный Chrome не требуется и не изменяется.

Команда безопасна для повторного запуска. Если `vendor` или `../shlz-ui` уже
существуют, setup принимает их только в закреплённом, чистом и полностью готовом
состоянии. При отличающемся commit, локальных изменениях, отсутствующих exports
команда завершается с конкретной подсказкой и не переписывает
существующий каталог. Сохраните нужные изменения сами, удалите или переместите
неподходящий каталог и повторите `make setup`.

`make ci-setup` является тем же setup-контрактом для чистого CI checkout. Setup
не запускает MariaDB/пилот, не создаёт и не меняет `.env`, не импортирует данные
и не изменяет пользовательские volumes.

## Запуск локального пилота

Создайте локальный конфиг и задайте уникальный bootstrap-пароль:

```bash
cp .env.example .env
# Отредактируйте FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD в .env.
make up
```

Откройте <http://127.0.0.1:8092/>. `make up` собирает и запускает Docker-контур,
а первый старт с пустыми volumes создаёт локальную БД и owner-admin из настроек
`.env`. Пароль не коммитьте и не публикуйте в логах.

Остановить контейнеры с сохранением данных:

```bash
make down
```

`make reset` удаляет локальные pilot volumes и данные. Выполняйте его только для
явно выбранного одноразового контура после резервной копии. Правила сохранения и
восстановления действующего стенда зафиксированы в
[`manual-pilot-stand-cutover-2026-09-07.md`](operations/manual-pilot-stand-cutover-2026-09-07.md)
и актуальном
[`stabilization-after-sleep-2026-09-08.md`](operations/stabilization-after-sleep-2026-09-08.md).

Маршрут пилота после входа: выбрать объект и состав, при необходимости скачать
PDF-шаблон, загрузить подписанный оригинал с датой документа (или добавить его
исправленную revision), затем отдельным действием открыть работы с фактической
датой. Ручной шаг регистрации/номер распоряжения не используется. Интеграция
Bitrix и задача #42 не входят в эту установку.

## Запуск проверок

Быстрая категория без общего полного прогона:

```bash
make test CATEGORY=unit
```

Полный локальный прогон:

```bash
make test
```

Полная команда поднимает одноразовую MariaDB из `compose.test.yaml`, по умолчанию
публикует её только на `127.0.0.1:23306`, сбрасывает `fmonitor2_test`, применяет
миграции и запускает все стадии. Эти данные отделены от pilot volumes. Два прогона,
использующие одну test DB/порт, нельзя запускать параллельно. Если `23306` занят
другим disposable test-контуром, остановите его либо задайте отдельный
`FMONITOR_TEST_DB_PORT` и обеспечьте отдельный Compose project.

`make fresh-test` дополнительно останавливает test Compose и удаляет его
одноразовый volume после прогона. Детали категорий и focused-команд находятся в
[`tools/verification/README.md`](../tools/verification/README.md).

Успешность полного setup/verification не следует выводить из одной команды в
этой инструкции. Фактические команды, окружение и результаты записываются в
[`development-setup-evidence-2026-09-08.md`](operations/development-setup-evidence-2026-09-08.md).

Исторический migration demo сохранён в [`app/demo/`](../app/demo/) и Git history;
это справочный артефакт, а не текущий маршрут запуска пилота.

## Изменение версий

Меняйте runtime/UI pins в `tools/delivery/dependencies.env`, TCPDF — через
`composer.lock`, затем выполните `python3 tools/delivery/render-dependencies.py`.
Dockerfile и Compose сохраняются в репозитории как сгенерированные файлы для
обычных `docker build`/`docker compose`; шаблоны лежат в `tools/delivery/*.in`.
`make doctor` и fast CI отклоняют расхождение с этими источниками.

Для генерации UI setup использует локальный read-only ZIP adapter на Python: он
поддерживает кириллические имена архивов, которые не читает системный unzip macOS.
PATH изменяется только для этой команды; системные утилиты и исходники UI сохраняются.
