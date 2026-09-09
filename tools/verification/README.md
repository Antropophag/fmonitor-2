# Проверки проекта

## Quality Graph текущего CI

`.github/workflows/quality-graph.yml` сохраняет прежний единственный набор
plan/fast/categories/verify. Итоговый read-only job формирует семь native reports
из GitHub outcomes, не запускает тесты повторно и честно показывает docs-only
пропуски. Integration node соответствует aggregate двух существующих shards.
Штатный publisher работает после завершения CI: обновляет итог, комментарий и
свои метки с разрешёнными владельцем правами. Непрерывный watch не включён.

Перед publisher read-only preflight отвергает отсутствующие, повторные и
неактуальные artifacts текущей попытки; старые artifacts сохраняются, когда
текущий набор полон. Ошибка preflight видна как failed publisher job; она не
выдаётся за успешно опубликованную проверку. Канонический verify сохранён.

Подготовка локального compiler toolchain: `uv sync --frozen --python 3.12.11`.
Проверка: `make quality-graph-validate`. После согласованного изменения graph,
report/preflight или workflow выполнить
`.venv/bin/python tools/delivery/render-current-quality-graph.py`, затем validate.
Этот renderer сохраняет текущие категории и формирует только интеграцию reports,
publisher и manifest. Не запускать `qg generate` поверх текущего workflow:
штатный генератор не сохраняет нашу full/docs-only матрицу и matrix shards.

При изменении декларации штатный publisher читает topology из base branch.
Первый bootstrap PR подтверждает CI, а реальную публикацию проверяют отдельным
representative PR после merge. До такой проверки готовность publisher не заявляется.

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

## Две части integration в CI

GitHub Actions запускает integration как matrix из двух отдельных VM, каждая
со своей MariaDB и обязательным teardown. Полный список валидируется перед
делением, сортируется по пути и распределяется через один: `1/2` и `2/2`.
Обе части обязательны; ошибка любой не даёт успешного verify.

```sh
# Только просмотр списка, без подключения к БД:
python3 tools/verification/ci.py list integration --shard 1/2
python3 tools/verification/ci.py list integration --shard 2/2

# В уже подготовленном изолированном test contour:
make test CATEGORY=integration SHARD=1/2
```

Без SHARD прежняя команда исполняет всю integration category. Selector разрешён
только для integration и только `1/2` либо `2/2`. Локально части можно выполнить
последовательно на одной testDB; для параллельного запуска нужны разные контуры
и порты. SHARD не передаётся во вложенные тестовые make-команды.

Решение владельца и границы:
[integration-sharding-owner-decision-2026-09-08.md](../../docs/operations/integration-sharding-owner-decision-2026-09-08.md).
