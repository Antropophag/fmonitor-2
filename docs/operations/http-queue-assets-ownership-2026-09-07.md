# Владение HTTP-очередью и assets — 2026-09-07

## Область работы

Реализация частей queue и asset из OpenSpec change
`restore-pilot-http-architecture-ownership` с сохранением поведения. Эта работа
не изменяла architecture baseline, schema, стенд, runtime data, Bitrix, CI или
remote state. `PilotE2ECoordinator` и извлечение user-access принадлежат другому
потоку.

## Свидетельства до изменения

На repository HEAD `6a919d9` focused architecture command сообщала три известных
SQL fingerprint из `rapid-pilot/ObjectQueue.php`:
`22c603a7f8f2a47d`, `9183136dfb6b49df`, `e193057503705cce`, а также рост hotspot
для `PilotE2ECoordinator.php` 268→308 и `rapid-pilot/router.php` 286→289.

Focused результаты до изменения:

- `rapid-pilot/verify-auth-hot-path.php`: PASS.
- `local_rbac_objects_route_admission_001_test.php`: PASS.
- `pilot_shlz_assets_001_test.php`: PASS.
- `pilot_route_csp_inventory_001_test.php`: PASS.
- `pilot_route_csp_001_test.php`: PASS.
- `rapid-pilot/verify-object-queue-filters.php`: существующее stale-падение
  source literal `completion statuses are not exposed in select` после переноса
  status labels в `InstallationStatusLabels`.
- `pilot_object_list_001_test.php`: существующее stale expectation отклонило
  текущий pagination copy `Показано`; cleanup path также сообщил только об
  изменении atime после чтения своего foreign sentinel.

## Реализация

- `app/PilotHttp/MariaDbObjectQueue.php` стал единственным владельцем существующих
  queue process-projection SQL, динамических фильтров, сортировки, pagination,
  проверки строк и date/status projection. `RapidPilotObjectQueue` сохраняет
  разбор request, авторизацию локального пользователя, completion/scheduling
  decoration и rendering.
- `rapid-pilot/FileTypeAsset.php` владеет существующим ограниченным поиском
  file-type SVG, публичным shlz-ui dist source, generic fallback и response
  headers. `rapid-pilot/router.php` теперь только делегирует совпавший route и
  содержит 282 строки.
- `verify-auth-hot-path.php` теперь требует публичный MariaDB read seam и запрещает
  process/checklist/completion query tables в rapid adapter, сохраняя без изменений
  существующее чтение authenticated user.
- Fixture alignment в `verify-object-queue-filters.php` сохраняет точные search
  columns, statuses и pagination metadata относительно нового read owner. Проверка
  рендерит filter и утверждает точные canonical labels и полный SHLZ Select contract
  через общий `InstallationStatusLabels`; ни один public assertion не удалён.

Все изменённые и новые source-файлы имеют mode `0644`.

## Свидетельства после изменения

- PHP syntax: PASS для обоих новых owners и всех четырёх изменённых PHP-файлов
  queue/router/verifier.
- `rapid-pilot/verify-auth-hot-path.php`: PASS.
- `rapid-pilot/verify-object-queue-filters.php`: PASS с выровненным ownership и
  public-render oracle.
- `local_rbac_objects_route_admission_001_test.php`: PASS.
- `pilot_shlz_assets_001_test.php`: PASS.
- `pilot_route_csp_inventory_001_test.php`: PASS.
- `pilot_route_csp_001_test.php`: PASS.
- `pilot_object_list_001_test.php`: без изменений сохраняет основное stale
  pagination-copy падение (`Expected []`, actual `copy=Показано `). Этот поток
  не редактировал ни одно expectation данного теста.
- Architecture check больше не сообщает ни одного ObjectQueue SQL fingerprint или
  router hotspot. Во время параллельного user-access extraction единственным
  оставшимся finding был новый 306-строчный hotspot
  `app/PilotHttp/PilotUserAccessHttpHandler.php`, принадлежащий другому потоку.
- `tools/architecture/baseline.json` остался byte-identical с SHA-256
  `9a67b19242bc1609d00c8a9e923246096b9730a89c988af6390ceb6541b5a6c8`.

Disposable local HTTP smoke подтвердил точные bytes основного файла и fallback
относительно публичных shlz-ui exports. SHA-256 `file-pdf-default.svg`:
`97e564168a005b67340870d42bdb828c8469fdb547bd6a22b9d8a97b18352a87`;
SHA-256 fallback для unknown-kind:
`881577a394f6290839d24825640b1ae3316fbf82c367478053ecd745ad057fa7`.
GET и HEAD вернули 200 с `image/svg+xml`, точным content length,
`public, max-age=3600` и `nosniff`. Временный server остановлен, captured files
перемещены в пользовательскую корзину.

Это implementation evidence, а не независимое одобрение Gate 5 и не заявление
production readiness или `VERIFY_OK`.
