# Проверки проекта

Основная команда — `make test`. Она выполняет полный набор, собирает отказы
всех девяти этапов и возвращает ненулевой код при любой ошибке.
`make fresh-test` дополнительно гарантирует teardown disposable testDB.
`make verify` и `make fresh-test-verify` — прежние совместимые aliases.
Маркеры VERIFY_OK/FRESH_TEST_VERIFY_OK сохранены для исторических evidence.

Для обычной правки локальный полный прогон перед полным CI не обязателен:

```sh
python3 tests/Verification/verification_ci_001_test.py
make test CATEGORY=unit
make test CATEGORY=governance
make test-db-reset migrate
make test CATEGORY=integration
make test CATEGORY=e2e
make test-env-down
```

Полный `make test` выполняет setup сам. При ручном запуске отдельных integration/E2E
категорий сначала поднимите тестовую БД и примените миграции указанной командой.
Не запускайте две DB-категории параллельно с одной локальной testDB.
В CI это отдельные виртуальные машины со своими данными и always teardown.

## Явный состав

`suites.tsv` задаёт группу, интерпретатор (`php`, `node`, `python3`) и путь через TAB.
Старые группы unit/db/characterization/e2e сохранены для совместимости полного
harness; историческая unit-группа включает инфраструктурные проверки.
`categories.json` задаёт новое непересекающееся распределение каждого файла по
unit/integration/e2e/governance. Категория unit — проверенный быстрый набор без БД.

```sh
bash tools/verification/run.sh list db
python3 tools/verification/ci.py list unit
python3 tools/verification/ci.py plan --base origin/main --event pull_request
```

Новый тест регистрируется явно. Незарегистрированный `*test.php` в двух основных
PHP-каталогах или `*_test.mjs` в Verification вызывает SETUP_FAILURE до исполнения.
Остальные Verification scripts добавляются явно: там также хранятся helpers и
standalone contracts. Пропущенный/лишний category mapping или повтор в full union
тоже ошибка. CLI list ничего не исполняет. Старый shell list не требует rg;
некоторые сами governance fixtures используют rg для проверки совместимости.

E2E назначен только одной группе и исполняется один раз. Bootstrap больше не
запускает пять самостоятельных контрактов дочерними процессами: они сохранены
в полном каталоге. Собственные bootstrap assertions и все E2E assertions сохранены.
Для полного покрытия используйте полный test, а не один bootstrap файл.

## Наблюдаемость

Каждый top-level тест выводит VERIFY и VERIFY_TIMING (категория, runtime, путь,
секунды, exit). Категории также выводят CATEGORY_RESULT и десять самых долгих тестов
в GitHub job summary. Старый shell runner измеряет целые секунды; новый category
runner использует monotonic clock с точностью вывода до миллисекунд. Дочерняя работа
входит во время родителя. Общий изменяемый timing-файл не используется.

Makefile дополнительно выводит VERIFY_STAGE_TIMING. Сумма test durations не включает
setup и не равна wall-clock параллельных jobs. Измерения ожидания и runner minutes
сравниваются отдельно.

Матрица, принятая владельцем, и оценка следующих анализаторов:
[verification-ci-matrix-2026-09-08.md](../../docs/operations/verification-ci-matrix-2026-09-08.md).
CI fast всегда обязателен; docs-only имеет явный DOCS_VERIFY_OK. Код, тесты,
конфигурация, неизвестный путь и release/schedule/manual требуют full VERIFY_OK.
