# INSTALLATION-COMPLETION-SCHEMA-001 — Gate 2 RED evidence

Дата: 2026-09-02  
Автор tests: fresh separately tasked RED agent `/root/completion_red`  
Normative spec: `specs/INSTALLATION-COMPLETION-SCHEMA-001.md`, approved hash
`c63ed10eb22d69ed7e86274a3008e6e991204166e44cb2ad9e8b00d1be686181`.

Это evidence Gate 2, а не test review, implementation approval или Done.
Production implementation не изменялась, task checkboxes не отмечались.

## Gate 1 byte-integrity resolution

После написания tests повторная команда
`sha256sum specs/INSTALLATION-COMPLETION-SCHEMA-001.md` вернула
`c6f3cf995a81d214559d4078696f82d6d2cfaa1123120cb91775fc5c6b5c5448`, а
owner approval фиксирует reviewed hash `c63ed10e…`. Orchestrator установил
точную причину: после явного согласования были изменены только status marker
`DRAFT` → `APPROVED` и ненормативная последняя фраза со ссылкой на approval
record, как заранее требовали Gate 1 task 1.4 и independent rereview.

`docs/operations/installation-completion-schema-owner-approval.md` теперь
фиксирует reviewed hash, post-approval administrative hash и exact bounded
transition. Sections 1–7, SHALL/MUST statements, manifests, scenarios и Done
criteria не менялись. Это разрешает передачу RED artifacts в Gate 3; fresh
reviewer всё равно обязан проверить traceability против reviewed normative
bytes и текущего administrative status, а любое нормативное отличие вернуть в
Gate 1.

## Добавленный executable contract

- `tests/InstallationProcess/installation_completion_schema_001_test.php`
  проверяет public migration class и canonical runner через test-owned literals:
  clean/repeat, exact manifests, populated root-only lossless upgrade, empty
  correction ledger, reverse partial, family-wide metadata/collation conflicts,
  binary-sorted names, other-prefix decoys, prefix 25/26 до DB access,
  non-utf8mb4 database-default zero mutation и correction-chain constraints.
- `tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php`
  запускает реальные authenticated ObjectQueue/card/checklist/completion HTTP
  seams под пользователем только с `SELECT/INSERT/UPDATE/DELETE`, сравнивает
  schema/row snapshots для exact/missing/drift и исполняет real bootstrap через
  guarded configuration adapter.
- `tests/Support/installation_completion_runtime_router.php` — изолированный
  HTTP process adapter; он вызывает production consumers и не подменяет их DB,
  readiness или response behavior.

JSON checklist operation/sync endpoints намеренно не включены: §5F явно
определяет, что они не потребляют completion family.

## Syntax и diff hygiene

Команда:

```text
php -l tests/InstallationProcess/installation_completion_schema_001_test.php
php -l tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
php -l tests/Support/installation_completion_runtime_router.php
git diff --check -- tests/InstallationProcess/installation_completion_schema_001_test.php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php tests/Support/installation_completion_runtime_router.php
```

Результат: все три файла `No syntax errors detected`; `git diff --check`
завершился с exit `0` и пустым output.

## Qualifying RED 1 — canonical owner отсутствует

Команда:

```text
php tests/InstallationProcess/installation_completion_schema_001_test.php
```

Exit: `255` (uncaught deterministic `TestFailure`). Relevant output:

```text
Uncaught TestFailure: clean public runner exact v1-v10 outcome
Expected: {"ok":true,"schemaVersion":10,"appliedVersions":[1,2,3,4,5,6,7,8,9,10]}
Actual:   {"ok":true,"schemaVersion":9,"appliedVersions":[1,2,3,4,5,6,7,8,9]}
```

Это intended RED: MariaDB доступна, v1–v9 успешно применены реальным runner,
stdout корректный JSON, но approved literal v10 ещё не зарегистрирован. Это не
ошибка setup, credentials или predecessor catalogue.

## Qualifying RED 2 — runtime всё ещё требует DDL

Команда:

```text
php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
```

Exit: `255` (uncaught deterministic `TestFailure`). Relevant output:

```text
Uncaught TestFailure: exact GET queue remains operational;
actual status=503,
body="Service unavailable. Reference: <12 lowercase hex>\n"
Expected: true
Actual: false
```

Fixture доказуемо проходит authentication/RBAC: до добавления test role public
seam возвращал `403 Access denied`; после выдачи только необходимых local role
permissions запрос достигает completion prerequisite. Database runtime identity
имеет только DML grants. Exact test-owned root + corrections family уже
существует, однако текущий `RapidPilotCompletionFlow::ensureSchema()` выполняет
`CREATE TABLE IF NOT EXISTS`; DML-only ObjectQueue поэтому закрывается `503`.
Это требуемый RED отсутствующего read-only readiness seam, а не setup failure.

После минимального GREEN первый assert перестанет прерывать каждый executable:
оставшаяся матрица должна тогда доказать exact v10 metadata, preservation,
correction-chain rejection, все HTTP outcomes, no partial HTML/redirect,
missing/drift zero mutation и bootstrap exit/stdout contract. Approved
expectations менять для GREEN нельзя.

## Gate 3 CHANGES_REQUESTED correction

Fresh reviewer `/root/completion_test_review` вернул первую редакцию в Gate 2.
Автор tests устранил каждый required finding без production/task/review edits:

- exact runtime fixture теперь независимо создаёт все 41 монтажных completion
  operation (сумма approved weights ровно 85), требует `303` от PTO POST и
  проверяет ровно один ожидаемый append; schema и все unrelated rows/counters
  остаются неизменными, а missing/drift используют тот же допустимый command и
  требуют zero DML;
- все failure seams требуют duplicate-sensitive `Cache-Control: no-store`;
  card/checklist HEAD требуют тот же declared `Content-Length`, что GET, при
  пустом wire body;
- first-create fingerprint теперь сравнивает ordered column type/null/default/
  AUTO_INCREMENT/generated/expression/charset/collation, полную index metadata,
  engine/table collation, exact FK names/columns/references/actions и три
  normalized CHECK identities;
- fresh exact fixtures мутируют root и correction columns/default/generated/
  character metadata/engine/collation/index order/subpart/type/visibility,
  FK absence/name/target/action и CHECK missing/changed/extra/duplicate; каждый
  случай требует deterministic conflict и exact before/after snapshot;
- добавлены alternate safe `utf8mb4_general_ci` success, invalid ASCII prefix
  before-access rejection и bootstrap exact/missing/drift matrix. Exact
  bootstrap допускает его ожидаемую fixture/product DML, но не completion
  history/schema mutation; missing/drift требуют zero mutation и no manifest.

Повторные focused команды после correction:

```text
php tests/InstallationProcess/installation_completion_schema_001_test.php
php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
```

Оба exit `255` и остаются qualifying intended RED. Первый по-прежнему получает
от доступного MariaDB/canonical runner terminal v9 вместо v10. Второй после
успешного RBAC и валидного 85%-fixture получает на exact-family ObjectQueue
`503` вместо `200`; fixture setup не является причиной failure.

Corrected artifact hashes:

```text
b70ef939e67152afd4059f419464d3d5c3cc5644448e415ca76348b1759e118d  tests/InstallationProcess/installation_completion_schema_001_test.php
8427a39674cf8d4c0e710f164bca14487b88a6fb54815fca699fc5119064618d  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
1331bc60607cd8a6c7f7e87a0bcb1d837a727c90e00465185dfd789c2ebbb682  tests/Support/installation_completion_runtime_router.php
986146019ef542e8fb3e62267d5399c03f932b657343f01a22475238f8b24fc2  tests/Support/inspection_planning_bootstrap_wrapper.php
```

## Gate 4 fixture correction

После начала минимального GREEN public v10 migration стала реально создавать
completion family. Полная approved matrix выявила два детерминированных дефекта
test setup; expectations и production contract при исправлении не менялись.

До исправления schema command завершался `SETUP_FAILURE`:

```text
Can't create table ...reverse_tmp_fm2_pilot_completion_fact_corrections
(errno: 121 "Duplicate key on write or update")
```

Причина — MariaDB требует уникальные имена FK constraints во всей schema, а
несколько одновременно живых prefixed exact families повторяли нормативные
имена `fk_completion_correction_root` и
`fk_completion_correction_previous`. Исправление освобождает family tables
только после полного assertion/snapshot каждого случая; decoy isolation
проверяется до cleanup. Mutation cases используют fresh exact family, поэтому
ни одна conflict expectation не стала слабее.

До исправления runtime command завершался `SETUP_FAILURE`:

```text
Table 'exact_fm2_pilot_completion_facts' already exists
```

Причина — fixture после успешного public v10 runner повторно выполняла
test-owned `CREATE TABLE`. Исправление удалило этот duplicate creator: exact
берётся непосредственно из canonical runner, missing получается deliberate
`DROP` corrections, drift — deliberate `ALTER` corrections. Это одновременно
повышает fidelity public deployment seam.

После исправления:

```text
php tests/InstallationProcess/installation_completion_schema_001_test.php
exit 0
PASS: INSTALLATION-COMPLETION-SCHEMA-001 migration and chain matrix

php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
exit 255
TestFailure: exact GET queue observable status
Expected: 200
Actual: 503
```

Schema matrix теперь проходит setup и все behavior assertions. Runtime matrix
также проходит v10 migration, exact/missing/drift construction, RBAC и 85%
fixture и останавливается только на оставшемся production behavior: exact
DML-only ObjectQueue ещё возвращает `503`, а не `200`.

## Runtime provenance prerequisite correction

Инструментированная диагностика Gate 4 (diagnostic code после фикса удалён)
показала exact exception после успешной DML-only completion readiness:

```text
Table '<prefix>fm2_migration_classification_provenance' doesn't exist
```

Это не completion schema RED: public ObjectQueue после readiness выполняет
свой landed read contract и требует classification proof для operational case.
Runtime fixture теперь создаёт отдельную минимальную test-owned provenance
table и ровно одну строку
`('operational_case', 71, 4512, 'native_candidate')`. Также case fixture
содержит согласованные с публичным card/read contract registered order,
installer snapshot и RFC3339 opening audit. Ни один из этих facts не создаёт и
не имитирует completion readiness: обе completion tables по-прежнему приходят
только из public canonical v10 runner.

Порядок runtime matrix изменён на `missing`, `drift`, `exact`, чтобы
missing/drift assertions исполнялись даже пока следующий exact consumer
остаётся RED. Оба hostile состояния проходят полный public HTTP набор и
доказывают `503`, exact no-store/Retry-After/body/HEAD length, отсутствие
partial HTML/redirect и byte-exact zero DML/schema repair до cleanup. Prefix
cleanup выполняется только после assertions и освобождает глобальные exact FK
names для следующего deliberate state.

После provenance correction exact ObjectQueue проходит с `200`; первый failure
продвинулся к следующему consumer:

```text
TestFailure: exact GET card observable status
Expected: 200
Actual: 503
```

Таким образом прежний queue failure доказан как fixture prerequisite, а не как
повод для test-specific production accommodation. Оставшийся card outcome —
поведенческий результат после успешно пройденных missing/drift completion
guards и exact ObjectQueue.

### Exact card/checklist/POST follow-up

Временный direct production-reader probe (полностью удалён до final hashes)
доказал, что `MariaDbObjectCardReader` проходит ту же fixture и под admin, и
под DML-only identity. Exact card `503` возникал раньше reader: runtime env
задавал неиспользуемый `FMONITOR_SHLZ_UI_ROOT`, тогда как production dependency
явно требует public export path `FMONITOR_SHLZ_CSS_PATH`. После передачи
`../shlz-ui/packages/styles/dist/shlz.css` exact HTTP matrix проходит:

- ObjectQueue GET `200`;
- card GET/HEAD `200`;
- checklist GET/HEAD `200`;
- valid 85% completion POST `303` и ровно один approved PTO append;
- schema и все unrelated rows/counters сохраняются.

Для valid card fixture добавлены только уже утверждённые landed facts:
registered order, один employed installer snapshot и RFC3339 opening audit.
Они удовлетворяют существующим card invariants и не участвуют в completion
readiness.

### Bootstrap follow-up

Следующий setup failure был в guarded bootstrap adapter: production bootstrap
получил новую реальную зависимость `CompletionFlow.php`, но wrapper не создавал
symlink. После добавления dependency adapter достигает production behavior.

Hostile bootstrap cases переставлены перед exact, как и HTTP cases. Первый
missing-family production result теперь:

```text
Expected: exit 70, stdout {"ok":false,"reason":"MIGRATION_FAILED"}, empty stderr
Actual: exit 255, empty stdout, uncaught RuntimeException on
       rapid-pilot/docker-bootstrap.php:49 (Schema migration failed)
```

Это уже не fixture/setup failure: canonical v10 state deliberate missing,
bootstrap достигает completion readiness и корректно обнаруживает её failure,
но production bootstrap пока не маппит её в approved redacted public outcome.
Если этот mapping станет GREEN, exact DML-only bootstrap дополнительно выявляет
production runtime DDL на `docker-bootstrap.php:51`: `CREATE TABLE IF NOT
EXISTS <legacy-prefix>fm_maintable` получает `CREATE command denied`. Approved
§5E требует удалить runtime DDL, а не выдавать тесту DDL grant.

Временная tagged instrumentation в `PilotHttp.php`, direct reader probe и
server-log hook после диагностики полностью удалены.

### Full exact bootstrap prerequisite inventory

Перед следующей fixture edit проинвентаризирован весь
`rapid-pilot/docker-bootstrap.php`, а не только очередной failure:

| Порядок | Действие | Что предоставляет fixture | Что production не вправе делать под DML-only |
|---|---|---|---|
| 1 | planning v9 + completion v10 readiness | canonical runner exact families | repair/create completion family |
| 2 | legacy object readiness | exact test-owned `legacy_fm_maintable` | runtime legacy DDL |
| 3 | process v1 apply + workforce readiness | canonical v1–v10 | schema mutation на repeat |
| 4 | generation sentinel update | exact sentinel table + existing singleton row | `CREATE TABLE IF NOT EXISTS` |
| 5 | identity bootstrap | canonical identity tables; empty configured superadmin set | identity schema DDL |
| 6 | deprecated table cleanup | отсутствие deprecated tables | `DROP TABLE IF EXISTS` |
| 7 | OTIZ bootstrap | заранее созданные current OTIZ tables/indexes | семь `CREATE TABLE IF NOT EXISTS` и conditional `ALTER` |
| 8 | pilot fixture/import | legacy row, provenance, order/installer/case facts | schema ownership |
| 9 | ready manifest | isolated writable HOME | публикация до успешных prerequisites |

Bootstrap fixture теперь до передачи DML-only identity создаёт exact generation
sentinel и singleton row, а также текущий OTIZ schema set test-owned DDL
literals. Это setup schema construction, не execution под runtime principal;
before/after assertions всё равно обнаруживают любой bootstrap DDL/schema
drift. Completion tables намеренно не создаются этим helper: их единственный
источник остаётся public canonical v10 runner, после чего missing/drift
формируются deliberate mutation.

Следовательно, оставшиеся DDL statements на bootstrap lines 68, 73–75 не
являются отсутствующими fixture prerequisites. Даже при существующей таблице
MariaDB требует `CREATE`/`DROP` privilege для `IF [NOT] EXISTS`; approved
DML-only contract требует заменить их read-only readiness/DML либо удалить,
а не расширять runtime grants.

### Removal of the circular OTIZ fixture seam

Первая редакция bootstrap prerequisite helper ошибочно вызывала production
`RapidPilotOtiz::bootstrap()` для создания семи таблиц. Это позволяло бы
production implementation и fixture измениться вместе и делало approved
read-only seam self-invalidating.

Вызов production полностью удалён. Независимые test-owned literals теперь
определяют все семь prerequisites:

- snapshots, objects, allocations, issues и evidence;
- payment closures с exact `unique_reversal`;
- OTIZ events.

Fixture отдельно проверяет семь InnoDB/`utf8mb4_unicode_ci` tables, ожидаемое
число ordered columns для каждого manifest и полную metadata
`unique_reversal` (`NON_UNIQUE=0`, ordinal 1,
`reverses_payment_closure_id`, BTREE, visible). Sentinel также остаётся
test-owned exact DDL + singleton row. Bootstrap получает явные test-only
`@shlz.ru` superadministrator credentials, потому что active superadministrator
является конфигурационным prerequisite DML, а не schema readiness.

Drift sensitivity исполняется отдельно от fixture construction: production
read-only OTIZ readiness сначала принимает exact test-owned set, затем обязан
отвергнуть независимо (1) отсутствующую events table и (2) удалённый
`unique_reversal`. Оба hostile prefix после assertion удаляются. Поэтому
production readiness не может стать слабее или изменить собственный schema
creator вместе с fixture незаметно для теста.

После независимой замены и production GREEN:

```text
php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
exit 0
PASS: INSTALLATION-COMPLETION-SCHEMA-001 DML-only runtime matrix
```

Полный executable теперь проходит missing/drift bootstrap exit 70 до
product/fixture mutation, exact HTTP/HEAD/checklist/completion POST, exact
bootstrap exit 0, expected sentinel/identity/fixture DML и публикацию единственного
ready manifest без completion schema/history mutation.

## Gate 5 return to Gate 2 — reserved index and session state

Independent Gate 5 review выявил два пропуска approved tests. Gate 2 дополнен
без production/task/review edits.

Reserved-name matrix использует два независимых test-owned состояния:

1. raw approved corrections DDL до normalization — реальный crash window между
   auto-committed `CREATE TABLE` и последующим `DROP INDEX`; MariaDB создаёт три
   index parts с именем `fk_completion_correction_previous`;
2. exact normalized family, после чего hostile fixture явно добавляет
   composite index с тем же reserved именем.

Оба состояния требуют deterministic `SCHEMA_MIGRATION_CONFLICT`, binary exact
table name и byte-exact zero mutation. Ни одно не может быть отфильтровано как
exact runtime-ready schema. Families освобождают schema-global FK names после
snapshot/result capture.

Session-state matrix независимо задаёт `@@SESSION.FOREIGN_KEY_CHECKS`:

- clean success: `0 → 0`;
- clean success: `1 → 1`;
- failure после root/corrections CREATE и denied normalization `ALTER` у
  limited `SELECT,CREATE` principal: `0 → 0`.

Failure fixture достигает реального denied ALTER и проверяет restore после
exception; отдельные database/user удаляются до assertions, поэтому RED не
загрязняет следующий run.

Команды:

```text
make test-env-up
php tests/InstallationProcess/installation_completion_schema_001_test.php
```

MariaDB contour поднят и healthy. Current intended RED:

```text
TestFailure: successful clean migration restores caller FOREIGN_KEY_CHECKS=0
Expected: 0
Actual: 1
```

Это qualifying behavior RED ST2, не setup failure. После его GREEN executable
дойдёт до reserved crash/hostile cases, которые должны проявить S1 blanket
filter как второй intended RED без изменения approved expectations.

### Gate 4 test-helper contradiction correction

Первоначальный generic `icsCreateCorrections()` оставлял MariaDB auto supporting
index, поэтому обычные mutation cases не начинались из approved exact-final
schema. Helper разделён:

- `icsCreateRawCorrections()` выполняет literal raw CREATE и сохраняет
  трёхчастный reserved index;
- `icsNormalizeCorrections()` удаляет только этот supporting index и сохраняет
  исходный session `FOREIGN_KEY_CHECKS`;
- `icsCreateCorrections()` raw-creates, затем normalizes и используется всеми
  обычными exact fixtures;
- dedicated `reserved_crash_` единственный намеренно использует raw helper без
  normalization.

MariaDB не может rebuild некоторых CHECK/FK mutations после удаления
supporting index, хотя final table остаётся рабочей. Поэтому correction-member
mutation fixtures сначала вносят ровно свою hostile mutation в raw table, затем
test-owned helper удаляет reserved index до before-snapshot. В результате
каждый generic conflict case содержит approved final metadata плюс только
заявленное отличие; crash case сохраняет actual CREATE-before-DROP state, а
hostile reserved case начинает из normalized exact family и вручную добавляет
то же имя.

После production correction и helper split:

```text
php tests/InstallationProcess/installation_completion_schema_001_test.php
PASS: INSTALLATION-COMPLETION-SCHEMA-001 migration and chain matrix
```

Все session-state, crash-window, hostile reserved-index и прежние approved
schema/constraint/history cases проходят; test helper не оставляет session-state
leak.
