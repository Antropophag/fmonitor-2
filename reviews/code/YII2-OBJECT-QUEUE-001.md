# YII2-OBJECT-QUEUE-001 — code review

## Подготовка Gate5 — root,2026-09-10

Root автор normative spec и всех tests; implement76_queue (sol/low) автор
production implementation. Независимый review76_queue писал только review records.
Gate3 и shell delta APPROVED; отдельная architecture approval ADR0004 разрешает
ровно YiiInspectionPlanning::scheduleInspection. Gate5 verdict пока отсутствует.

Публичные owners созданы через YiiRuntime/InstallationProcessFactory, SQL в
MariaDb Yii adapters, одно соединение/transaction planning. Manifest accessors v17/
v19 переиспользуют существующие schemas; HTTP не мигрирует. Новый baseline delta
ровно один seam. Probe полного --write-baseline дал постороннее уменьшение старого
baseline; исполнитель отменил этот файл и внёс только approved seam, debt allowances
не расширены. Старый stand/runtime.php не менялись.

Шесть final focused commands закончились exit0:
- php tests/Yii2/yii2_inspection_planning_001_test.php — /tmp/76-final-planning.log
- php tests/Yii2/yii2_inspection_planning_concurrency_001_test.php — /tmp/76-final-concurrency.log
- php tests/Yii2/yii2_object_queue_lineage_001_test.php — /tmp/76-final-lineage.log
- php tests/Yii2/yii2_queue_readiness_001_test.php — /tmp/76-final-readiness.log
- php tests/Yii2/yii2_object_queue_001_test.php — /tmp/76-final-http.log
- php tests/Yii2/yii2_object_queue_browser_001_test.php — /tmp/76-final-browser.log

make architecture-check exit0 (полная qualification + actual7),
/tmp/76-final-architecture.log. Architecture unit59/59; inventory15/15; CI policy,
jobs compose, change-verification и runtime storage GREEN в /tmp/76-*-green.log.
Yii auth/users/OTIZ/readiness и original_ready_queue_manual GREEN. Никакого local
full suite; authoritative exact-source full CI ещё впереди.

Первичные implementation failures были сохранены только в terminal tool history
исполнителя, отдельных log files для них нет: Yii double-encoded event payload,
completion-v17 historical manifest mismatch, затем status selection test ожидал
неверный value attribute. Первые два исправлены в implementation; для последнего
root сделал тест наблюдающим actual selected option (Gate3 delta approved).
Не выдаём поздние GREEN logs за историю прежних неудач.

Root подготовительная проверка до Gate5 остановила минификацию, чтение web user
из DAO и второй command seam. После исправлений owner принимает actor явно,
command orchestration находится в YiiInspectionPlanning, persistence primitives
не образуют второй schedule command. Максимальный новый файл145 строк; control
flow раскрыт, query joins разделены. Точная maintainability оценка остаётся Gate5.

Первый visual batch показал потерю mobile icons/logout/admin navigation и palette.
Root написал реальные navigation regressions, получил intended RED и отдельный
Gate3 approval до UI correction. Final desktop/mobile batch просмотрен root:
видны icons/условные admin links/logout и cyan/orange status variants; overflow нет.
Дополнительных косметических циклов нет. Final screenshot directory:
/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/yii-user-access-d80fafb685c7/.
Impeccable detector []: /tmp/76-final-impeccable.json. Lint/diff-check GREEN.

Нормативный долг сохранён явно: historical completed-count status filter отличается
от displayed weighted progress после retraction; в этом refactor не исправляется.
Карточка/calendar остаются следующими Yii migrations; runtime cutover не выполнен.

## Independent Gate 5 review — sol/low, 2026-09-10

Reviewed the complete staged candidate from reconstructible snapshot
`/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-queue-gate5`,
base `dd503a104fa9dfb299e15be810cc6affb88f7856`, patch SHA-256
`e5282562afbd590226fec3115dce4eaa19326c7676a86864845ee14bc179cd9a`,
restored at `/private/tmp/fmonitor-76-queue-gate5`. The reviewer authored neither
the production code nor tests. Review covered the full production/config/baseline
diff, the normative specification and approved Gate 3 tests; it does not approve
the untouched stand/cutover or the remaining card/calendar scope of issue #76.

### Complete findings

1. **HIGH — Yii readiness is not an exact comparison with the shared canonical
   manifests and can fail open on structural drift.** Locations:
   `app/InstallationProcess/MariaDbYiiSchemaFingerprint.php:27-36,54-58`.
   `queueFamiliesReady()` reduces every completion and inspection-evidence table
   to `columnsMatch()`, comparing only ordered column names. A changed column type,
   nullability/default/collation, index, CHECK or foreign key therefore remains
   “ready”, even though the existing public mysqli predicates compare the exact
   v17/v19 manifests. For planning tables, `matches()` compares columns and indexes
   but reduces CHECK/FK validation to counts, so replacing a required constraint
   with a different one of the same type also passes. This violates the explicit
   readiness contract in `specs/YII2-OBJECT-QUEUE-001.md:137-141` and the design's
   valid/missing/column/index/CHECK/FK parity decision. The current readiness test
   catches full structural faults only for planning; its completion/evidence cases
   add an unexpected column, which `columnsMatch()` detects, and therefore cannot
   expose this fail-open behavior.

   Correction: keep the change narrow to `MariaDbYiiSchemaFingerprint`: compare
   each accepted shared-manifest alternative in full, including complete column
   metadata, ordered indexes, CHECK expressions and FK columns/references/rules,
   using the existing v17/v19 accessors and without copying schema literals or
   changing mysqli predicates. Add Gate 2 delta cases that mutate a completion
   and evidence column attribute/index/constraint while retaining column names,
   plus a same-count wrong CHECK or FK for planning; require both public readiness
   transports and Yii HTTP/owner paths to reject without repair. Independently
   review that test delta before returning to Gate 4/5.

The transaction owner, authorization at both public entry points, append-only
schedule/event writes, replay/concurrency handling, HTTP mapping/CSRF, query
lineage/projection semantics, shell integration and the single approved baseline
seam otherwise conform to the reviewed contract. No additional findings were
identified.

I independently reran the focused planning and readiness tests on the exact
snapshot; both passed (`PASS`), confirming valid-schema behavior but also the
coverage limitation described above. `git diff --check` for production/config/
baseline was clean. The retained six GREEN logs, architecture qualification and
neighbor checks are consistent with the reviewed source. No full suite was run.

**Gate 5 verdict: CHANGES_REQUESTED.** Because the correction needs new sensitive
readiness expectations, return this narrow part to Gate 2, obtain independent
Gate 3 approval for the test delta, then correct production and resubmit the full
result to Gate 5. The exact architecture approval for
`InstallationProcess\YiiInspectionPlanning::scheduleInspection` remains valid;
this finding requires no new public seam or baseline allowance.

## Исправление HIGH после independent Gate3 delta

Root расширил readiness test на21 структурный drift; delta независимо APPROVED
(snapshot71eb9337, полный intended RED). Исполнитель исправил только полный Yii
fingerprint и отформатировал два current-manifest accessors. Root сравнил app tree
с первым Gate5 snapshot: изменены ровно MariaDbYiiSchemaFingerprint,
InstallationCompletionDetailsSchemaMigration и InspectionPhotoContentIndexSchemaMigration.
UI, controller, transaction и projection bytes не менялись этим исправлением.

Теперь сравниваются engine/table collation, все поля column metadata, ordered
indexes, нормализованные CHECK expressions и FK name/sequence/column/target/
update/delete. Completion перебирает обе предусмотренные schema alternatives;
evidence использует current v19 definitions. Literal schemas и старые mysqli
predicates не переписаны. Fingerprint148 строк, читаемый flow, baseline не растёт.

Все21 новых проб вернули HTTP503 и restored PASS:
/tmp/76-gate5-readiness-final-green.log. Остальные affected GREEN:
/tmp/76-gate5-{planning,concurrency,http,lineage,browser}-green.log.
Полный make architecture-check (qualification +7) GREEN:
/tmp/76-gate5-architecture-final.log. Root прочитал конечные логи, lint/diff GREEN.
UI не менялся: предыдущая финальная visual confirmation остаётся действительной;
нового косметического просмотра не выполнялось. Full CI и final Gate5 ещё ожидаются.

## Independent Gate 5 corrected re-review — sol/low, 2026-09-10

Reviewed the complete corrected candidate from reconstructible snapshot
`/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-queue-gate5-corrected`,
base `dd503a104fa9dfb299e15be810cc6affb88f7856`, patch SHA-256
`f717528cfdc8f0fab5915cd4b5d2627af5d4879940e4a0f55dac6bf002bd4c9c`,
restored at `/private/tmp/fmonitor-76-queue-gate5-corrected`. Re-review covered the
entire previous finding list and the production delta against the first Gate 5
snapshot. The only application changes are the Yii fingerprint and the two shared
current-manifest accessors; all other previously reviewed production bytes are
unchanged.

### Complete findings

None. The prior HIGH finding is resolved. `MariaDbYiiSchemaFingerprint` now
compares engine and table collation, complete ordered column metadata, complete
ordered index metadata, FK identity/sequence/columns/targets/update/delete rules,
and normalized CHECK expressions. Completion evaluates both supported v17 current
manifest alternatives; inspection evidence uses the current v19 manifests; and
planning uses the same complete comparator. The implementation consumes the
existing public manifest accessors, does not copy literal schemas, alter the
mysqli predicates, repair schema at request time or add a public seam. The CHECK
normalization follows the established completion fingerprint behavior and the
new helpers remain within the repository size guardrail.

Independent verification on the exact snapshot:

- `php tests/Yii2/yii2_queue_readiness_001_test.php` passed after all 21 new
  `READINESS_PROBE` cases returned HTTP 503 and restored canonical readiness;
- `make architecture-check` passed the complete HTTP qualification and all seven
  architecture rules;
- `git diff --check` for the three corrected application files was clean.

The retained focused planning, concurrency, HTTP, lineage and browser GREEN logs
remain applicable because those implementation bytes did not change. No full
suite was run; one authoritative exact-source CI remains the delivery step after
the reviewed candidate is bound to its checkpoint.

**Gate 5 verdict: APPROVED.** The corrected candidate conforms to
YII2-OBJECT-QUEUE-001 for this bounded queue/planning slice. The exact architecture
approval remains limited to
`InstallationProcess\YiiInspectionPlanning::scheduleInspection`; no additional
baseline, SQL, dependency or policy allowance is approved. This verdict does not
claim the stand cutover, card/calendar migration or completion of the whole #76.
