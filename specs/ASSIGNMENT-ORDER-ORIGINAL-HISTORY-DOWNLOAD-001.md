# ASSIGNMENT-ORDER-ORIGINAL-HISTORY-DOWNLOAD-001

Версия0.1,2026-09-07. Gate1 required before tests/implementation.

## Простыми словами

Портал должен читать историю принятых оригиналов и получать точный PDF выбранной
revision через production API владельца evidence. API заранее проверяет весь файл
и возвращает готовые bytes; ошибка чтения не превращается в частично успешную
выдачу PDF. HTTP отдельно проверит роли и область доступа — этот порт не даёт прав.

## 1. Public in-process seam

Namespace FMonitor2\AssignmentOrderOriginal:

```php
AssignmentOrderOriginalHistoryReaderFactory::create(mysqli $db, string $privateStorageRoot, string $prefix = '')
    : AssignmentOrderOriginalHistoryReader;
interface AssignmentOrderOriginalHistoryReader {
    public function readHistory(int $objectId, int $orderId, int $afterRevisionNumber = 0, int $limit = 50): AssignmentOrderOriginalHistoryLookup;
    public function prepareDownload(int $objectId, int $orderId, string $revisionId): AssignmentOrderOriginalDownloadLookup;
}
```

Both lookups expose public readonly `status` (AssignmentOrderOriginalHistoryStatus:
found/not_found/invalid_argument/unavailable). History lookup exposes nullable
`page`; download lookup nullable `download`. Value non-null iff found.
Page exposes metadata():array; prepared download exposes metadata():array and
bytes():string. Все возвращаемые metadata и bytes immutable-by-copy; результат
не удерживает connection, transaction, descriptor или lock, не требует close().

Trusted consumer проверяет actor/capability/object scope ДО обращения. Порт не
HTTP endpoint, не application gate, не grant; не использует legacy engineer field.
Original owner сохраняет владение SQL/storage. Diagnostic EvidenceReader не
используется как runtime dependency. Никаких mutation/audit/DDL/FS writes.

Factory не подключается к DB и не читает SQL/FS/environment. Prefix ASCII
[A-Za-z0-9_]{0,25}; root — непустой absolute Unix path <=4096bytes без NUL.
Invalid scalar configuration throws AssignmentOrderOriginalHistoryConfigurationUnavailable,
fixed message `Original history configuration unavailable.`, code0, previousnull,
до обращения к закрытому DB или FS. Root existence/ownership проверяются только
при download; metadata history не зависит от доступности private root.

## 2. Source snapshot and status distinctions

objectId/orderId positive; afterRevisionNumber0..4294967295, limit1..100.
RevisionId inherits original command grammar:1..80visibleASCII bytes0x21..0x7e,
без slash/backslash. Invalid arguments дают invalid_argument до SQL/FS.

Closed/no-selected-DB/non-utf8mb4/active caller TX/missing dependencies → unavailable.
Caller connection не переключается/закрывается и не переподключается. Методы
владеют одной consistent read-only snapshot только на idle borrowed connection;
success/failure оставляет её idle. Caller TX не коммитится/откатывается и не
используется как nested transaction. Filesystem phase download идёт после release
owned DB snapshot; historical immutable revision остаётся допустимой при новой correction.

Только registered immutable selection source. Reuse owning-module registered
composition +StoredReader/lineage/request/audit/event proof, уже approved для
original application reference. No legacy-source conversion or current/latest-order
policy. Missing case/selected order/accepted original → not_found. Existing malformed
or inconsistent backing → unavailable. Missing selected revision within a valid
original → not_found. Foreign order's revision не выдаётся, даже если существует.
Принятый original с отсутствующим/повреждённым файлом → unavailable при download,
но healthy metadata history остаётся found. Никакого empty success при corruption.

## 3. Exact metadata

History page metadata ordered keys:
objectId,caseId,orderId,orderVersion,rootOriginalId,currentRevisionId,
compositionIdentity,compositionSha256,composition,totalRevisions,
afterRevisionNumber,nextAfterRevisionNumber,revisions.

composition: installers ascending {tabId,fullName,position}; engineer
{userId,fullName,position}, как в immutable selection snapshot.
revisions: ascending revisionNumber, строго >afterRevisionNumber, до limit.
Revision record ordered keys:
revisionId,revisionNumber,previousRevisionId,documentDate,uploadedAt,actorUserId,
sha256,byteSize,correctionReason.
Initial previousRevisionId/correctionReason null; correction сохраняет original
previous pointer/reason. No current user directory substitution for historical facts.

totalRevisions/currentRevisionId относятся к одной snapshot. nextAfterRevisionNumber
равен номеру последней возвращённой revision только если в этой snapshot есть ещё
revisions; иначе null. Cursor beyond/equal total returns found empty revisions and
null next, сохраняя context. Later correction добавляет новую revision; ранее
полученная page не меняется. Новый pending order не скрывает историю прежнего.

Prepared download metadata ordered keys:
objectId,caseId,orderId,orderVersion,rootOriginalId,compositionIdentity,
compositionSha256,composition,revision.
revision — тот же exact9field record выбранной immutable revision. Нет current
pointer, privateContentIdentity, path, filename, configuration или diagnostic inventories.
Bytes exactly match selected revision size/hash, не current leaf вместо выбранной.

## 4. Prepared PDF and filesystem boundary

Only accepted native content identity `content-sha256-{sha256}` maps to owning
storage content file. Identity не используется как произвольный path. Root должен
быть canonical realpath, без symlink components, с approved owner/root mode0700/0750.
Existing digest lock и PDF — regular non-symlink files owned by current process UID,
mode0600, single hard link. Lstat/fstat identity coherence проверяется при открытии;
nonregular/symlink/alias/foreign owner/malformed backing fail closed.

Используется существующий digest lock shared LOCK_SH|LOCK_NB, открытый read-only;
никакого create/chmod/repair lockfile. Busy exclusive lock → unavailable, no wait loop.
PDF читается только после lease. Before found проверены exact1..20971520bytes,
EOF/no extra byte и SHA256 accepted revision. Ошибки open/read/validation/release/close
дают unavailable без download value/partial bytes. Все descriptors/locks освобождены
перед return. Storage inventory/state files не читаются и не переписываются.
Повторный вызов снова проверяет file; уже подготовленный immutable buffer сохраняет
исходные bytes даже после correction или временной недоступности source file.

## 5. Independent native examples and rejection checks

Existing native selected fixture case/object4512, order81/v1, composition-81-v1,
SHA5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a;
installer7001 «Монтажник 7001»/«Монтажник», engineer73 «Инженер теста»/«Инженер строительного контроля».
Initial native accepted PDF1.7:327bytes,
SHA78162c8976f51bd62ed4c49dc4d9dc8884b839de770f6b447449c55305e1bb62,
date2026-09-04, actor18, frozen uploadedAt2026-09-05T09:00:00Z.
Correction PDF1.4 literal from approved original HTTP fixture:327bytes,
SHA4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784,
date2026-09-03, actor18, same frozen clock, reason `Уточнена дата и файл`.
Generated root/revision identities берутся только из public command receipts.

Native tests at prefix0and25 exercise healthy selection→two accepted originals
before intended RED. History limit1 yields rev1/next1 then rev2/nextnull; old/new
PDF buffers differ and each has independently fixed hash/size/metadata. Page/value
copies cannot mutate results; repeated reads and new correction preserve old values.

Missing order/original/revision, cross-order binding, malformed DB backing, invalid
arguments/prefix/rootshape, null/closed/charset/ambient TX covered. Sentinel proves
active caller TX ownership; all DB facts/DDL/private file names/hashes/modes unchanged
by successful or failed reads. Root unavailable affects only download. Real missing,
short/extra/same-size corrupt PDF, symlink/hardlink/nonregular file and missing lock
fail closed and fixtures are restored. Real public storage digest-exclusive lease
blocks download, release allows exact bytes. Native stream resource counts stable.
Exact20MiB accepted PDF is prepared intact; above-limit metadata/backing is rejected.
No permission-probe/fake native interception or verification reader in runtime.

## 6. Delivery boundary

Gate1→native RED→independent Gate3→minimal owning-module GREEN→relevant original
regressions+architecture/lint/diff→independent Gate5. No HTTP/assigned-engineer grant,
application-date decision, opening, protected-E2E edit or migration version. Parent
HTTP Done remains all roles/history/download/restart/fullVERIFY; this dependency
alone never closes it. Full launch remains unproven.
