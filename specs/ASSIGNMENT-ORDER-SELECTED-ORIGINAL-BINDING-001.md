# ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001

Версия0.1, 2026-09-06. Candidate independent Gate1.

## Простыми словами

Сохранённый native выбор становится основанием прямой загрузки original без
PDF-шаблона. Original и выбор проверяются под одним case lock: заменить pending
выбор и одновременно принять original для старого выбора нельзя. История accepted
original по-прежнему исправляется append-only, даже если уже есть следующий выбор.

## 1. Authority и граница

Actors сотрудник/Руководитель ФКР. Наследуются exact original command/capabilities,
PDF inspection/storage/leases, date, replay/correction и audit approvals
ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001, original combined Gate5 на360db9a;
SELECT-001 v0.11 section17 и native Gate5; REGISTERED-COMPOSITION-READER-001 v0.1.
Новых продуктовых решений нет. REPLACE_PENDING и no-template-storage уже approved.

Единственная mutation seam остаётся
`AssignmentOrderOriginalApplication::submitAssignmentOrderOriginal(command)`.
Ни renderer, ни physical prepare, HTTP или fixture SQL её не подменяют.
Новый явно выбранный constructor в namespace FMonitor2\AssignmentOrderOriginal:

```php
ProductionAssignmentOrderOriginalFactory::createForSelections(
    \mysqli $db, AssignmentOrderOriginalProductionConfig $config,
    AssignmentOrderOriginalFreshTerminalReaderFactory $freshTerminalReaders
): AssignmentOrderOriginalApplication;
AssignmentOrderSelectedOriginalVerificationFactory::create(
    \mysqli $db, AssignmentOrderOriginalProductionConfig $config,
    AssignmentOrderOriginalFreshTerminalReaderFactory $freshTerminalReaders,
    AssignmentOrderOriginalClock $clock,
    ?AssignmentOrderOriginalPersistenceObserver $observer = null
): AssignmentOrderOriginalApplication;
```

Оба constructor связывают один service и один native composition policy/repository.
Verification меняет только clock и existing persistence observer; реальны
selection, original repository, authorization, PDF parser, private storage и audit.
Clock используется command и storage. Production использует system clock/inert
observer. Existing create/createRecoveryReady остаются physical-only; fallback
по наличию таблиц запрещён. Safe-log-first configuration и existing fixed
configuration exception сохраняются; prefix0..25. Fresh reader обязателен.

## 2. Согласованный источник и write-target

Target reader использует approved registered composition validators и один RR
read-only snapshot. Historical registered reader продолжает возвращать любой
валидный immutable состав; currentness относится только к original write-target.
Новый constructor допускает selection-owned source, без legacy writer adoption.
Same-case legacy ownership/dual/orphan/schema error → UNAVAILABLE, без fallback.
Other-case identity → NOT_FOUND без чужих source probes, как existing reader.

Currentness внутри того же snapshot:
- latest selection данного case — допустимый target;
- не latest, но существует valid accepted original root exact case/order/hash —
  состав доступен прежнему correction/replay/initial-existing protocol;
- не latest и accepted root отсутствует — NOT_CURRENT;
- отсутствующий/противоречивый source или malformed root inspection — UNAVAILABLE.
Root validity переиспользует existing original lineage validation; presence alone
не подтверждает accepted original. Совпавший terminal replay остаётся прежде
composition lookup по прежнему original protocol.

Добавляется внутренний `AssignmentOrderCompositionLookupStatus::NOT_CURRENT`
с backing `not_current`. Payload empty: requested case/order, null identity/hash/
engineer и empty installers. Shape mismatch — прежний persistence_failure.
Valid NOT_CURRENT → existing conflict/target_not_current, retryable=false.
NOT_FOUND → existing rejected/order_not_found; UNAVAILABLE → existing
failed/persistence_failure. Остальные original outcomes не меняются.

## 3. Locked commit и race outcome

Fresh original accepted transaction сначала блокирует exact installation case
row `FOR UPDATE` — ту же, что native selection. Затем выполняет registered source
и currentness check заново в этой transaction, без nested begin/commit/rollback.
Состав/hash должен совпасть с preflight accepted commit payload. Header/members
и original correction leaf проверяются существующими invariants. Correction
не требует быть latest selection, если accepted root валиден для exact identity.

Если pending selection заменена до lock, original facts не пишутся. После
подтверждённого rollback repository возвращает новый внутренний
`AssignmentOrderOriginalCommitStatus::COMPOSITION_NOT_CURRENT`
(backing `composition_not_current`). Commit protocol переводит его в existing
conflict/target_not_current и штатный terminal request+audit после release.
Неопределённый commit/rollback всегда сохраняет OUTCOME_UNKNOWN и прежнюю fresh
recovery; новый status не используется как предположение при потере acknowledgement.
Иные mismatch/query failures сохраняют existing persistence_failure semantics.

Старое выбранное основание остаётся доступным historical reader. Отказ после
private finalize использует прежний resource/lease/orphan protocol; новая ручная
очистка файлов не появляется. Новых persisted status/reason/schema не требуется:
target_not_current уже принадлежит original conflict contract.

## 4. Preconditions и preservation

Combined registry/selection/original schemas заранее установлены и проверены
controlled setup/startup. Factories/commands не выполняют DDL, backfill или lazy
repair. Canonical migration registration — следующий integration пакет; version
здесь не резервируется, актуальный frontier13.

Upload/correction не меняют selection rows/members, physical assignments, actual
start, checklist attribution или opening. Only original facts/audit/private bytes
по прежнему approved protocol. PDF templates/HTTP/application/opening исключены.
Authorization original остаётся exact upload/correct capability; selection grant
не даёт original grant автоматически. Every denied invocation audit сохраняется.

## 5. Executable evidence и Done

Только task-owned synthetic DB/private directory, без production data/secrets.
Public selection создаёт81/version1, installer7001, engineer73; hash literal
5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a.
Original clock2026-09-05T09:00:00Z, documentDate2026-09-04, approved valid PDF corpus.
Первый RED: production/verification selected constructor принимает original без
physical order/template, создаёт revision1 с exact composition; repeated request
replays silently, selection/opening/assignment facts сохраняются.

Обязательные последующие cases: replace_pending81→82 и initial original82;
загрузка replaced81 → target_not_current без PDF read в preflight; replacement
между preflight и lock → тот же conflict с terminal/audit и без original facts;
original wins lock → последующий replace_pending получает original_already_accepted;
correction accepted81 после new_order82 сохраняет обе истории; malformed/other-case
source nondisclosure; authorization и original replay/rollback regression.
Barriers используют existing original observer/owned worker, не production fault hook.

Done: Gate1→RED→independent Gate3→GREEN→native original/selection/reader regression,
architecture-check и independent Gate5 на exact clean SHA. Parent integration и
полный портал не объявляются ready без full make verify/VERIFY_OK, CI и deploy/
restart/golden-path. Unaffected approvals повторно не запрашиваются.
