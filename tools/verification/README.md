# Состав и измерение проверок

`run.sh` — публичный runner. `suites.tsv` задаёт три поля через TAB:
группа, интерпретатор (`php`, `node`, `python3`), путь относительно корня.
Порядок строк — порядок исполнения. При добавлении теста зарегистрируйте его
явно; неизвестный `*test.php` в InstallationProcess/AssignmentOrderComposition
или `*_test.mjs` в Verification вызывает SETUP_FAILURE до исполнения.
Остальные PHP/Python файлы Verification включаются явно: там есть вспомогательные
и отдельно запускаемые контракты, поэтому весь каталог не считается одной suite.

```sh
bash tools/verification/run.sh list unit
bash tools/verification/run.sh list db
bash tools/verification/run.sh list characterization
bash tools/verification/run.sh list e2e
python3 tests/Verification/verification_inventory_001_test.py
```

`list` проверяет полноту каталога, выводит `runtime<TAB>path`, не запускает
интерпретаторы, не подключается к БД и не требует rg. Исполнение остаётся через
`make unit-test`, `make db-test`, `make characterization-test`, `make e2e-test`.
`unit` — историческое имя смешанного набора, пока не гарантия отсутствия Docker/HTTP.
Новый focused контракт inventory работает без Docker и реальной БД.

После каждого top-level теста runner выводит:

```text
VERIFY_TIMING suite=unit runtime=php file=tests/... seconds=2 exit=0
```

Длительность измеряется Bash SECONDS с разрешением одна секунда. Дочерние тесты
включены во время родителя; это не число всех вызовов и не benchmark быстрых функций.
Нет общего файла метрик: stdout каждого процесса можно сохранить отдельно.
Сумма длительностей тестов не включает setup/migrations/architecture/lint и не равна
wall-clock параллельных CI jobs. Не суммируйте её с временем тех же suite jobs.

`make verify` по-прежнему выполняет весь gate, `make fresh-test-verify` добавляет
обязательный teardown тестового окружения. Изменения branch protection,
обязательных CI jobs и матрицы выбора здесь нет. Все прежние membership сохранены,
включая известный повтор E2E в DB, standalone E2E и bootstrap child. Их устранение
и разделение быстрых/unit/integration/E2E/governance — следующие срезы issue #25.

При регистрации нового теста дополните независимые baseline-ожидания в
`verification_inventory_001_test.py` с явным review изменения состава; не обновляйте
их автоматически ради GREEN. Снимки digest относятся к исходному d5f8f2d.
