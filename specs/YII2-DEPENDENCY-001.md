# YII2-DEPENDENCY-001 — единая Composer-зависимость runtime

## Простыми словами

Yii2 и TCPDF устанавливаются из одного зафиксированного `composer.lock`, поэтому
локальная установка, CI и runtime используют один настоящий Composer autoloader.
Старый git checkout TCPDF и изготовленный вручную `vendor/autoload.php` удаляются
из setup после успешного перехода. Этот срез не меняет HTTP-поведение приложения.

## Public seam

Публичные границы среза:

- `bash tools/delivery/setup.sh [--check]` для локальной установки и проверки;
- `bash tools/delivery/setup-composer.sh [--check]` как лёгкий общий Composer
  bootstrap, вызываемый полным setup и `.github/actions/setup-runtime/action.yml`;
- repository `composer.json` и `composer.lock` как единственный dependency graph.

## Acceptance contract

1. `composer.json` требует ровно `yiisoft/yii2:2.0.55` и
   `tecnickcom/tcpdf:6.11.4`; `config.platform.php` равен `8.4.0`. Другие
   существующие requirements не изменяются без отдельного решения.
2. `composer.lock` согласован с `composer.json`, содержит ровно по одной locked
   package версии Yii2 `2.0.55` и TCPDF `6.11.4` и остаётся общим для web/console,
   локальной установки, CI и runtime image.
3. `tools/delivery/dependencies.env` фиксирует Composer `2.10.3` и SHA-256
   официального `composer.phar`
   `7a2d379d5b8ffdaa028580ef26494c36d2feef4b178d3dd1473a4dbc5e17c8d6`.
   Setup скачивает только точный versioned artifact, проверяет digest до запуска
   и не исполняет непроверенный installer script.
4. Fresh `setup-composer.sh` выполняет non-interactive Composer install по lock в
   task-owned staging directory и атомарно публикует готовый `vendor/`, включая
   настоящий `vendor/autoload.php`. Он не клонирует TCPDF отдельно и не копирует
   `rapid-pilot/tcpdf-autoload.php`. Повтор использует тот же lock; существующий
   несовместимый `vendor/` не исправляется молча и выдаёт `SETUP_FAILURE`.
5. `setup-composer.sh --check` не пишет и не скачивает. Отсутствующие local phar и
   `vendor/` допустимы как dependencies, которые установит setup. Если они есть,
   команда проверяет digest phar, manifest/lock, загрузку Yii `2.0.55` и TCPDF
   `6.11.4` и platform requirements. `setup.sh --check` вызывает этот режим.
6. CI action после настройки PHP вызывает repository-owned
   `tools/delivery/setup-composer.sh`. Action не запускает полный `ci-setup.sh`,
   Docker/shlz build и не выбирает плавающую версию Composer через сторонний
   setup default.
7. Ошибка download, SHA-256, lock validation, package install или platform check
   завершает setup до build/test, удаляет task-owned staging и не публикует
   частичный `vendor/` как готовый. Любой существующий `vendor/` сохраняется byte-for-byte.
8. Старые проверки TCPDF не отключаются: их смысл переносится на locked package
   version и реальную загрузку `TCPDF_STATIC` через Composer autoload.

## Intended RED

`python3 tests/Verification/yii2_dependency_setup_001_test.py` падает на текущем
git-checkout TCPDF, искусственном autoloader, отсутствующих Composer pins и CI
wiring. GREEN требует реализации через указанные public seams.
