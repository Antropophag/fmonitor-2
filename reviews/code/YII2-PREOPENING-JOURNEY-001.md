## Независимый Gate 5 review — YII2-PREOPENING-JOURNEY-001

### Source identity

Проверен exact restored snapshot:

- Checkout: `/private/tmp/fmonitor-76-preopening-gate5`
- Base и `HEAD`: `401a2345535a1e3c991373c7afd85acf6a2997d3`
- Snapshot: `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-preopening-gate5`
- Manifest base совпадает с checkout.
- Заявленный SHA-256 patch: `f5b361867138371e37dfb6c0ec237d5f7ee6fec1c9a38b7f3b60665c8ad8f1f2`
- Фактический SHA-256 `source.patch`: тот же.
- Заново сформированный `git diff --binary --full-index 401a…` побайтово совпадает со snapshot patch.
- Production scope относительно base: 35 файлов `app/` и `config/`, 1864 добавления, 5 удалений.
- `vendor/` — обычный скопированный каталог, не symlink; Composer source isolation соблюдена.
- `git diff --check` для `app/config` прошёл.

Source полный для заявленного candidate; постороннего runtime fallback в diff не обнаружено.

### Независимость и авторство

Reviewer не писал specification, tests или production этой задачи и не порождал агентов.

По delivery record:

- specification/tests и решения по границам: root;
- production: отдельные sol/low исполнители `preopening_executor`, `card_executor`, `preopening_js`;
- последний полный восьмифайловый test delta независимо одобрен Gate 3 по snapshot `31f32b4f…`.

Root docs/reviews использовались только как источник требований, происхождения и evidence, не как implementation approval.

### QualityGraph и evidence

QualityGraph plan заново сформирован с pinned base `f804f3f6fa7baa7264a51b6e503c13f48406f2d7` и текущим `verification-input.json`.

Результат:

- `CHANGE_VERIFICATION_OK`
- plan SHA-256: `177268a9a244b600a4162cf4555eada636f8da61b1e4a1bec21bcf167046df38`
- 2 acceptance groups
- 4 категории: `e2e`, `governance`, `integration`, `unit`
- 39 команд, включая обязательный будущий CI `make test`

Прочитаны normative spec, approved Gate 3 review, OpenSpec design/tasks/input, migration inventory, pilot product/data contracts и retained evidence. Проверены:

- последние пять `76-final-mapping-green-*`;
- `76-final-template-split-{routes,http}`;
- `76-final-architecture-check`: PILOT-HTTP-AUTH PASS, architecture 7 rules PASS;
- focused GREEN для всех новых HTTP/concurrency/uncertain-commit suites;
- native-owner inventory, включая сохранённый первоначальный concurrency timeout и последующий canonical PASS;
- шесть QualityGraph obligations;
- production-image/PDF package evidence;
- final browser result, отсутствие asset failures и desktop/mobile screenshots;
- exact moved PDF asset hash: app-копия совпадает с прежним asset.

Полный local/CI и уже GREEN suites не повторялись: новой причины для повторного выполнения не возникло. Stand не изменялся.

### Проверка candidate

Подтверждены:

- strict canonical positive IDs и numeric bounds до DTO coercion;
- отдельные UUID contracts: original принимает v1–5, selection/opening — v4;
- native Yii identity/session/CSRF, exact active roles и capability grants;
- selection closed grammar и CSRF-only template POST;
- raw PDF transport, metadata grammar, explicit length и 20 MiB bound;
- canonical 11-field native original Result, replay и raw-byte preservation;
- безопасные 4xx/503 mappings без exception/config/SQL leaks;
- immutable history, exact historical downloads и full-integrity HEAD;
- uncertain persistence → 503 без автоматического mutation retry;
- opening/application replay, reapplication и durable actor/time lineage;
- card provenance, truthful detail degradation, read-only GET/HEAD;
- отсутствие controller-owned SQL/domain writes и outer mixed transactions;
- request-scoped native resource closure;
- сохранение целых unchanged native owners;
- owned PDF asset и production-image packaging;
- существующие возвратные пути, shell/navigation и bounded visual evidence.

### Finding

1. **MEDIUM — document-ссылки в UI не следуют фактической contextual admission.**

   Locations:

   - `app/YiiRuntime/Controllers/ObjectCardController.php:32–41`
   - `app/YiiRuntime/Controllers/SelectionController.php:38–40`
   - `app/YiiRuntime/Views/object-card.php:69–72`
   - `app/YiiRuntime/Views/selection.php:31,38`

   На карточке инженер признаётся `documentRole`, если он указан в текущем выбранном составе:

   ```php
   (int) ($card['controlEngineer']['userId'] ?? 0) === $this->actor()
   ```

   Но нормативная spec разрешает engineer history/download только при наличии **current application**, что сервер правильно проверяет через `AssignmentOrderApplicationReader` в `OriginalHistoryController::admission()`. До применения состава карточка поэтому показывает инженеру ссылки на PDF/history, которые гарантированно отвечают `403`.

   Аналогично selection UI показывает upload/correction links по `original.upload` или `original.correct`, не требуя обязательный для формы `original.read`. Пользователь с точными write/select grants, но без read grant, получает видимое недоступное действие.

   Это не privilege escalation: серверная защита остаётся корректной. Это реальное нарушение требований «Form additionally requires original.read» и «UI controls follow exact capabilities» и сломанный user return path.

   **Correction:** вычислять document/form affordances через один read-only application-level admission seam, совпадающий с серверными условиями:

   - engineer read — только current applied assignment;
   - upload/correction link — соответствующий write grant + builtin FKR/manager policy + `original.read`;
   - history/download — `original.read` + нормативная role/application union.

   Добавить чувствительный HTTP regression: engineer с принятым original до application не видит history/download links; после application видит; FKR/manager с upload/correct, но без `original.read`, не видит ссылку формы. Поскольку это изменение tests, оно требует обычного Gate 2/3 delta review, без ослабления текущих ожиданий.

## Verdict

**CHANGES_REQUESTED**

Остальная проверенная production-поверхность соответствует согласованному срезу и retained evidence. Verdict относится только к independent Gate 5 candidate; CI, merge или готовность stand не заявляются.
## Независимый Gate 5 review — YII2-PREOPENING-JOURNEY-001

### Source identity

Проверен exact restored snapshot:

- Checkout: `/private/tmp/fmonitor-76-preopening-gate5`
- Base и `HEAD`: `401a2345535a1e3c991373c7afd85acf6a2997d3`
- Snapshot: `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-preopening-gate5`
- Manifest base совпадает с checkout.
- Заявленный SHA-256 patch: `f5b361867138371e37dfb6c0ec237d5f7ee6fec1c9a38b7f3b60665c8ad8f1f2`
- Фактический SHA-256 `source.patch`: тот же.
- Заново сформированный `git diff --binary --full-index 401a…` побайтово совпадает со snapshot patch.
- Production scope относительно base: 35 файлов `app/` и `config/`, 1864 добавления, 5 удалений.
- `vendor/` — обычный скопированный каталог, не symlink; Composer source isolation соблюдена.
- `git diff --check` для `app/config` прошёл.

Source полный для заявленного candidate; постороннего runtime fallback в diff не обнаружено.

### Независимость и авторство

Reviewer не писал specification, tests или production этой задачи и не порождал агентов.

По delivery record:

- specification/tests и решения по границам: root;
- production: отдельные sol/low исполнители `preopening_executor`, `card_executor`, `preopening_js`;
- последний полный восьмифайловый test delta независимо одобрен Gate 3 по snapshot `31f32b4f…`.

Root docs/reviews использовались только как источник требований, происхождения и evidence, не как implementation approval.

### QualityGraph и evidence

QualityGraph plan заново сформирован с pinned base `f804f3f6fa7baa7264a51b6e503c13f48406f2d7` и текущим `verification-input.json`.

Результат:

- `CHANGE_VERIFICATION_OK`
- plan SHA-256: `177268a9a244b600a4162cf4555eada636f8da61b1e4a1bec21bcf167046df38`
- 2 acceptance groups
- 4 категории: `e2e`, `governance`, `integration`, `unit`
- 39 команд, включая обязательный будущий CI `make test`

Прочитаны normative spec, approved Gate 3 review, OpenSpec design/tasks/input, migration inventory, pilot product/data contracts и retained evidence. Проверены:

- последние пять `76-final-mapping-green-*`;
- `76-final-template-split-{routes,http}`;
- `76-final-architecture-check`: PILOT-HTTP-AUTH PASS, architecture 7 rules PASS;
- focused GREEN для всех новых HTTP/concurrency/uncertain-commit suites;
- native-owner inventory, включая сохранённый первоначальный concurrency timeout и последующий canonical PASS;
- шесть QualityGraph obligations;
- production-image/PDF package evidence;
- final browser result, отсутствие asset failures и desktop/mobile screenshots;
- exact moved PDF asset hash: app-копия совпадает с прежним asset.

Полный local/CI и уже GREEN suites не повторялись: новой причины для повторного выполнения не возникло. Stand не изменялся.

### Проверка candidate

Подтверждены:

- strict canonical positive IDs и numeric bounds до DTO coercion;
- отдельные UUID contracts: original принимает v1–5, selection/opening — v4;
- native Yii identity/session/CSRF, exact active roles и capability grants;
- selection closed grammar и CSRF-only template POST;
- raw PDF transport, metadata grammar, explicit length и 20 MiB bound;
- canonical 11-field native original Result, replay и raw-byte preservation;
- безопасные 4xx/503 mappings без exception/config/SQL leaks;
- immutable history, exact historical downloads и full-integrity HEAD;
- uncertain persistence → 503 без автоматического mutation retry;
- opening/application replay, reapplication и durable actor/time lineage;
- card provenance, truthful detail degradation, read-only GET/HEAD;
- отсутствие controller-owned SQL/domain writes и outer mixed transactions;
- request-scoped native resource closure;
- сохранение целых unchanged native owners;
- owned PDF asset и production-image packaging;
- существующие возвратные пути, shell/navigation и bounded visual evidence.

### Finding

1. **MEDIUM — document-ссылки в UI не следуют фактической contextual admission.**

   Locations:

   - `app/YiiRuntime/Controllers/ObjectCardController.php:32–41`
   - `app/YiiRuntime/Controllers/SelectionController.php:38–40`
   - `app/YiiRuntime/Views/object-card.php:69–72`
   - `app/YiiRuntime/Views/selection.php:31,38`

   На карточке инженер признаётся `documentRole`, если он указан в текущем выбранном составе:

   ```php
   (int) ($card['controlEngineer']['userId'] ?? 0) === $this->actor()
   ```

   Но нормативная spec разрешает engineer history/download только при наличии **current application**, что сервер правильно проверяет через `AssignmentOrderApplicationReader` в `OriginalHistoryController::admission()`. До применения состава карточка поэтому показывает инженеру ссылки на PDF/history, которые гарантированно отвечают `403`.

   Аналогично selection UI показывает upload/correction links по `original.upload` или `original.correct`, не требуя обязательный для формы `original.read`. Пользователь с точными write/select grants, но без read grant, получает видимое недоступное действие.

   Это не privilege escalation: серверная защита остаётся корректной. Это реальное нарушение требований «Form additionally requires original.read» и «UI controls follow exact capabilities» и сломанный user return path.

   **Correction:** вычислять document/form affordances через один read-only application-level admission seam, совпадающий с серверными условиями:

   - engineer read — только current applied assignment;
   - upload/correction link — соответствующий write grant + builtin FKR/manager policy + `original.read`;
   - history/download — `original.read` + нормативная role/application union.

   Добавить чувствительный HTTP regression: engineer с принятым original до application не видит history/download links; после application видит; FKR/manager с upload/correct, но без `original.read`, не видит ссылку формы. Поскольку это изменение tests, оно требует обычного Gate 2/3 delta review, без ослабления текущих ожиданий.

## Verdict

**CHANGES_REQUESTED**

Остальная проверенная production-поверхность соответствует согласованному срезу и retained evidence. Verdict относится только к independent Gate 5 candidate; CI, merge или готовность stand не заявляются.

## Независимый Gate 5 CORRECTION review — contextual document admission

### Source identity и независимость

Проверен exact restored source `/private/tmp/fmonitor-76-preopening-gate5-admission-yii`:

- base и `HEAD`: `401a2345535a1e3c991373c7afd85acf6a2997d3`;
- snapshot: `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-preopening-gate5-admission-yii`;
- manifest содержит тот же base;
- заявленный и фактический SHA-256 `source.patch`:
  `929eb0cb7d0ce4dc6c1734c2f5ad48e0de68d8841971310c246a1a08535784b7`;
- заново сформированный `git diff --cached --binary --full-index 401a234…`
  имеет тот же SHA-256 и побайтово совпадает со snapshot patch;
- `vendor/` является обычным скопированным каталогом, не symlink;
- `git diff --cached --check` прошёл.

Reviewer не писал specification, tests или production candidate и не порождал
агентов. Scope ограничен единственным MEDIUM первого Gate 5 — parity UI/server
document admission — и риском, созданным его исправлением. Остальные 35 ранее
проверенных production-файлов не переоткрывались.

Относительно исходного reviewed source `/private/tmp/fmonitor-76-preopening-gate5`
проверены только новые `AssignmentOrderOriginalAccessQuery` и
`MariaDbAssignmentOrderOriginalAccessQuery`, их composition в
`PreopeningResources`, а также callers в `ObjectCardController`,
`SelectionController`, `OriginalController` и `OriginalHistoryController`.
Отдельно прочитан утверждённый Gate 3 correction delta в
`yii2_original_transport_001_test.php`.

### Проверенные требования и evidence

Нормативная матрица сохранена: engineer получает document read только по current
application; FKR/manager form affordance требует `original.read` вместе с
соответствующим write grant; raw initial/correction POST не получает нового read
gate. UI card/selection и server form/history используют общий read-only seam.
Grant query выполняется через переданный Yii DAO connection; существующие native
application reader/submission APIs используются целиком через caller-owned mysqli,
без outer transaction и без записи фактов.

Прочитаны последние independent admission Gate 3 PASS для corrected snapshot
`d1b73b7c…` и retained GREEN:

- `76-yii-dao-admission-original-transport.log`: оба сообщения PASS, включая
  FKR/manager raw initial/correction без `original.read`;
- `76-yii-dao-admission-architecture.log`: HTTP global-call qualification PASS и
  architecture 7 rules PASS;
- более ранние `76-admission-green-authorization.log` и
  `76-admission-green-browser.log`: PASS.

Quality Graph заново сформирован planner-ом с pinned base
`f804f3f6fa7baa7264a51b6e503c13f48406f2d7` и текущим
`verification-input.json`; `check` вернул `CHANGE_VERIFICATION_OK`. Plan SHA-256:
`fb75bb10c4b3781b9224ea1a90188d8552e6d9c88d9ac2f889ca30f708da371b`;
сохранены 2 acceptance groups, 39 commands и 4 категории: `e2e`, `governance`,
`integration`, `unit`.

GREEN suites повторно не запускались: существующий admission transport test не
создаёт failure Yii grant query на карточке и потому не чувствителен к найденному
риску; wholesale rerun не дал бы новой информации. Full CI/local full не запускались.

### Finding

1. **MEDIUM — object card превращает недоступность нового admission DAO в ложный
   успешный `200`.**

   Locations:

   - `app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalAccessQuery.php:21–45`
   - `app/YiiRuntime/Controllers/ObjectCardController.php:33–40`
   - для сравнения корректный caller:
     `app/YiiRuntime/Controllers/SelectionController.php:35–36`

   `readAccess()` намеренно сворачивает ошибку Yii grant query или current
   application reader в `status=unavailable`. Selection, form и history переводят
   этот статус в `503`, но object card читает только `canCorrect`/`canRead` и
   продолжает рендерить карточку с `200`, скрывая document controls. В исходном
   candidate ошибка `cap()`/`roleCodes()` доходила до внешнего `catch (Throwable)`
   карточки и возвращала `503`; correction поэтому создала новую тихую деградацию.
   Это нарушает fail-closed unavailable mapping и parity общего server/UI seam,
   хотя privilege escalation и запись фактов не возникают.

   **Correction:** в `ObjectCardController` после `documentAccess()` явно вернуть
   безопасный `503` (с сохранением HEAD semantics), если `status === 'unavailable'`,
   до render. Добавить чувствительный focused HTTP case: сделать grant/application
   admission source недоступным, потребовать card GET/HEAD `503`, отсутствие
   document links/partial success и неизменность DB/private files. Existing
   positive/negative visibility и raw write-only assertions не ослаблять.

## Verdict

**CHANGES_REQUESTED**

Первый MEDIUM по обычным состояниям admission исправлен, но correction создаёт
указанный MEDIUM unavailable-path regression. Verdict относится только к exact
CORRECTION source; CI, merge и готовность stand не заявляются.

## Финальный independent correction review

Свежий CLI reviewer gpt-5.6-sol/low, не автор spec/tests/production.

**APPROVED**

Источник:

- snapshot base: `401a2345535a1e3c991373c7afd85acf6a2997d3`;
- `source.patch` и восстановленный staged diff совпадают;
- SHA-256: `2ea96645170c3d0f792588b72c682f98a286fd075034c87d809706508de3d5ab`;
- `git diff --cached --check` — PASS.

Проверенный correction delta:

- `ObjectCardController`: `status=unavailable` возвращает безопасный `503` до рендера, включая HEAD semantics;
- `PreopeningController::domain()`: `SERVICE_UNAVAILABLE` отображается в `503`, а не `422`;
- относительно `/private/tmp/fmonitor-76-preopening-gate5-admission-yii` production отличается только этими двумя ветвями;
- тестовый delta — только чувствительная матрица восьми GET/HEAD запросов.

Evidence:

- original transport: PASS, включая четыре read-consumer `503`, восстановление схемы, отсутствие новых фактов/файлов и сохранение raw initial/correction `201` без read grant;
- architecture: HTTP qualification PASS, 7 rules PASS;
- Quality Graph пересчитан с pinned `f804f3f6…`: `CHANGE_VERIFICATION_OK`, 39 команд, 4 категории, 2 acceptance groups; plan SHA-256 `223c2fc8a8fc084fbd986b291c300e9bbc118ff23021bf880f9e8549679b06cf`.

Findings: отсутствуют в согласованном bounded scope. Full CI/local full не запускались; CI, merge и завершение всего #76 не утверждаются.
