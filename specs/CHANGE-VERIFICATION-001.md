# CHANGE-VERIFICATION-001

Простыми словами: до Gate 2 автор получает короткий исполняемый план проверок,
который покрывает заявленные acceptance seams и все реально изменённые файлы.
План нельзя незаметно использовать после изменения кода, спецификации, политики
или тестового каталога. Полный `make test` перед интеграцией сохраняется.

## Актор и публичный seam

Актор — автор change до Gate 2. Публичный seam:
`python3 tools/delivery/change-verification.py plan|check|run` с JSON input и
JSON plan. Input содержит непустые `planned_paths` и `acceptances`; каждый
acceptance имеет `spec_path`, стабильные `spec_id` и `acceptance_id`, `seam` и
непустое множество будущих или существующих test paths. Одна спецификация может
иметь несколько acceptance/seam mappings. Пути являются относительными repository paths.

## Нормативные требования

1. `plan --base REF --input FILE --output FILE` SHALL разрешить REF в commit и
   объединить declared planned paths с committed `REF...HEAD`, staged, unstaged и
   untracked paths. Deleted paths SHALL сохраняться как влияние. Результат SHALL
   быть canonical deterministic JSON: стабильная сортировка, argv arrays без
   shell string, `phase` и `rationale` у каждой команды.
2. Каждый acceptance SHALL отображаться в команды по его test paths. Поддержаны
   только repository test paths с runtime `python3`, `php` или `node`,
   определённым политикой. Один test path не может принадлежать двум mappings.
   Spec/spec path/acceptance ID, seam или test path без mapping,
   malformed/duplicate mapping и пустой
   итог SHALL завершаться `SETUP_FAILURE` до Gate 2.
   Категория существующего зарегистрированного acceptance test SHALL добавляться
   к required categories независимо от категории изменённого production path.
   Будущий ещё не зарегистрированный test остаётся допустимым.
   Каждый effective path, зарегистрированный в inventory как test, SHALL также
   добавлять свою exact category и собственный runtime argv; повторяющиеся argv
   SHALL исполняться один раз.
3. Каждый effective path SHALL классифицироваться одной однозначной boundary
   policy rule. Boundary требует перечисленные focused categories, которые policy
   отображает в executable argv, и обязательные boundary tests. Неизвестная или
   неоднозначная boundary SHALL fail closed. Категории SHALL существовать в
   `tools/verification/categories.json`; зарегистрированный существующий test
   SHALL согласовываться с его категорией. Каждый существующий test в
   `category_argv[C]` SHALL иметь ровно категорию C; каждый boundary test SHALL
   иметь категорию, явно требуемую этой boundary. Категории других paths не
   могут маскировать неверную связь.
4. План SHALL связывать SHA-256 digests graph, category inventory, policy,
   настоящей planner-спецификации, каждой acceptance `spec_path`, change input и
   planner source, а также resolved base, HEAD и полный snapshot actual paths/status
   и content digests. `check` SHALL независимо
   пересчитать план из этих inputs и отклонить stale input/source/policy/spec/
   graph/inventory, изменённую boundary, added/removed/renamed/staged/unstaged/
   untracked работу, удалённый файл, tampered plan или base drift.
5. `run --plan FILE --phase focused` SHALL сначала выполнить `check`, затем
   последовательно выполнить ровно canonical commands выбранной фазы через argv
   без shell, прекратить запуск после первого ненулевого результата и вернуть его.
   Planner SHALL не выполнять DB reset/migrate/teardown и не добавлять `make test`
   в focused plan. RED Gate 2 остаётся правдивым результатом child test и не
   преобразуется в synthetic pass.
6. Для любой code, test, policy или unknown boundary итоговый integration plan
   SHALL включать `make test`; это существующая обязательная полная CI гарантия,
   но `run --phase focused` её не дублирует. План не заявляет upstream Quality
   Graph capability и не меняет publisher, CI или inventory.
7. Governance/tooling test-only boundary SHALL добавлять только DB-free focused
   obligations. HTTP, persistence и OTIZ money boundaries SHALL добавлять свои
   конкретные auth, storage и snapshot tests. Подготовка MariaDB остаётся
   обязанностью соответствующего integration/full runner, не planner.

## Наблюдаемые rejected cases

- Missing/malformed JSON, duplicate JSON keys, absolute/escaping paths, duplicate
  acceptances/tests, unsupported runtime и пустой plan: `SETUP_FAILURE`, no plan.
- Omitted required category/test или изменение любого bound input после plan:
  `check` и `run` ненулевые, ни один child command не запущен.
- Unknown/ambiguous changed boundary: fail closed, no empty or docs-only success.

## Пример

При planned `app/PilotHttp/Action.php`, acceptance `EXAMPLE-001` / `http:action`
и test `tests/InstallationProcess/action_001_test.php` focused command равна
`["php","tests/InstallationProcess/action_001_test.php"]`; boundary policy может
добавить обязательный auth test. Integration содержит `["make","test"]`.
