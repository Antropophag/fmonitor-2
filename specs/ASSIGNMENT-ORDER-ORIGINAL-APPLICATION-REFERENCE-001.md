# ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001

Версия0.1,2026-09-07. Independent Gate1 required.

## Простыми словами

Application-команде нужна проверенная ссылка на уже принятый original и способ
убедиться перед записью, что его revision не изменилась. Original module остаётся
владельцем чтения lineage/backing; другие модули не копируют его SQL. Этот срез
только читает metadata и удерживает общий case lock в транзакции вызывающего.
Он не утверждает текущую доступность PDF bytes, не скачивает файл и не применяет состав.

## 1. Public in-process seam

Namespace FMonitor2\AssignmentOrderOriginal:

```php
AssignmentOrderOriginalApplicationReferenceFactory::create(mysqli $db, string $prefix = '')
    : AssignmentOrderOriginalApplicationReferenceReader;
interface AssignmentOrderOriginalApplicationReferenceReader {
    public function readCurrent(int $objectId, int $orderId): AssignmentOrderOriginalApplicationReferenceLookup;
    public function confirmCurrent(AssignmentOrderOriginalApplicationReference $reference): AssignmentOrderOriginalApplicationGuardStatus;
}
```

Lookup exposes public readonly `status` and nullable `reference`.
Lookup status enum values: found, not_found, invalid_argument, unavailable.
Reference non-null iff found. Reference exposes `metadata():array`, returning
immutable-by-copy metadata (mutating returned array cannot mutate reference).
Guard status enum: matched, changed, unavailable.

Это data port для доверенного application consumer, не HTTP endpoint и не grant.
Consumer самостоятельно проверяет actor/capability до доступа к evidence и пишет
факты только через собственный public application seam. Reader не выводит роли
из legacy responsstroicontrol и не создаёт capabilities. Configuration prefix:
ASCII `[A-Za-z0-9_]{0,25}`; invalid prefix throws
AssignmentOrderOriginalApplicationReferenceConfigurationUnavailable с fixed
message `Original application reference configuration unavailable.`, code0,
previous=null, до SQL. Factory не открывает connection, не читает environment/FS.

## 2. Snapshot reference

Positive object/order IDs обязательны; ноль/negative дают invalid_argument до SQL.
Closed connection, missing selected DB, non-utf8mb4 connection, active caller
transaction, missing/invalid source dependencies → unavailable. Caller connection
не закрывается, не переключает DB/charset и не переподключается.

readCurrent владеет своей consistent read-only snapshot только при idle connection.
После success/failure она завершена; при active caller transaction метод её не
коммитит/не откатывает и не начинает nested transaction. DB/facts/audits/FS не пишутся.

Source: только registered immutable selection. Reuse approved
MariaDbRegisteredCompositionQuery и AssignmentOrderOriginalStoredReader в одном
owned snapshot через existing owning-module helpers. Case/object/order binding,
composition identity/hash, root/current leaf и request/audit/event backing
проверяются. Отсутствие case/selected order/accepted original → not_found;
несогласованное или malformed существующее evidence → unavailable, не empty success.
Legacy order не преобразуется в selected application reference.

metadata exact ordered keys:
objectId,caseId,orderId,orderVersion,rootOriginalId,revisionId,revisionNumber,
documentDate,sha256,byteSize,uploadedAt,compositionIdentity,compositionSha256,composition.
Последнее поле: installers = ascending snapshots {tabId,fullName,position};
engineer = {userId,fullName,position}. Состав относится к этой immutable selection;
date/uploadedAt берутся из original, не clock/template/selection date.
Нет privateContentIdentity, filesystem path, originalFilename, DB configuration
или opaque guard internals в metadata. PDF bytes/FS inventory не читаются.

Reference binds issuing reader instance, selected database name, native connection
identity and connection charset. It seals the validated root/current revision,
registry/selection header and ordered selection members for later comparison.
Only metadata transfers by value; references from another reader cannot confirm.

## 3. Borrowed transaction guard

confirmCurrent требует reference этого reader, тот же DB/connection/charset и
active caller-owned write transaction. Отсутствие этих условий → unavailable;
reader не начинает, коммитит или откатывает caller transaction и не закрывает DB.
SQL wait policy остаётся у caller/его configured connection; reader её не меняет.

Guard сам получает `FOR UPDATE` lock на canonical fm2_installation_cases по
case/object identity, затем выполняет current locking reads source rows. Поэтому
даже ранее установленная caller repeatable-read snapshot не подменяет current
root pointer. Общий case lock соответствует approved original/selection writers.
Успешный guard удерживает lock до commit/rollback вызывающего.

Если current root имеет другую valid revision identity, возвращается changed.
Если source остался exact тем же, matched. Missing/corrupt immutable source,
изменение sealed данных при прежней revision, read-only transaction/SQL failure,
foreign reference или DB/charset/connection switch → unavailable. Во всех outcomes
caller transaction остаётся под его контролем; уже взятые locks отпускает caller.

Guard не устанавливает global latest/applicability/chronology policy между
разными распоряжениями и не применяет original. Он подтверждает только exact
reference к указанному order. Application layer отдельно определяет допустимость
перехода и выполняет свою запись в этой же транзакции перед release case lock.
Повтор guard для того же reference в той же transaction идемпотентен: no facts.

## 4. Independent checks

Native selected fixture: object/case4512, order81 version1, installer7001,
engineer73; no template. Accepted PDF327bytes SHA
78162c8976f51bd62ed4c49dc4d9dc8884b839de770f6b447449c55305e1bb62,
documentDate2026-09-04, frozen original uploadedAt2026-09-05T09:00:00Z.
Composition identity composition-81-v1, SHA
5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a.
Generated root/revision IDs берутся только из результата public original command;
остальные values — independently fixed approved selected-original example.

- readCurrent returns exact14 fields and snapshot; DB/FS unchanged, connection idle.
- same proof + caller write TX → matched, repeated matched, caller TX still active;
  caller rollback leaves all facts unchanged.
- public original correction date03 → old reference changed, new reference date03,
  prior immutable source still readable through its existing original owner.
- raw fixture corruption byteSize327→328 without request backing → unavailable;
  guard detects sealed metadata change after a successful snapshot.
- changed returned metadata copy cannot alter internal reference.
- foreign reader reference, active caller readCurrent, closed connection, wrong
  DB/charset, invalid IDs/prefix and missing selected/original data fail as above.
- real prefix25 works; wrong prefix never falls back to unprefixed facts.
- temporarily unavailable task-owned private root does not break metadata read;
  restored private files retain exact hashes (this is not byte-availability proof).
- real original correction worker waits on guard's case lock, then completes only
  after caller rollback. Tests use bounded owned worker/reap and public original
  command, not function shadow, native interception or permission probes.

Gate1→native RED after healthy selection/original setup→independent Gate3→minimal
GREEN→relevant original/selection regression+architecture/lint/diff→independent Gate5.
No schema version reservation, HTTP/role binding or parent application completion.
