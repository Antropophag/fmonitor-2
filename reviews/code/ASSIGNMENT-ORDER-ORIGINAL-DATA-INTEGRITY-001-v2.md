# Независимый Gate 5 review: DATA-INTEGRITY-001 v2

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Reviewed implementation: `4ed122cac564ae22c4505d3799a1059b04fb64a9`
- Implementation baseline: `94a17bfef8175669a2265ebd03a33e77c143bfee`
- Specification SHA-256: `30f402ceb136cd72fcc97801a28c51c9186ce78e8ea5454bf0e98ee272814bd9`
- Parent specification SHA-256: `461577954b8f89f07900e1b3ebb217ceec3f981421d6420f51e79d9c4f5f18ee`
- Verdict: **APPROVED**

Reviewer не писал reviewed source или tests. Review ограничен task 5.9:
закрытием findings предыдущих application/MariaDB reviews, fresh native recovery
и wiring, step-11 concurrent replay, TCP-only `localhost` и точными ранее
утверждёнными fixture/oracle поправками.

## Source assessment

Предыдущие reviews
`ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001-application-v1.md` и
`ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001-mariadb-core-v1.md` повторно
использованы для неизменившегося core. Их единственный source blocker закрыт:
`AssignmentOrderOriginalLineageSnapshot` теперь вызывает `containsRevision`
ровно для известных invocation current/target/query-revision IDs и требует его
совпадения с immutable list также для NOT_FOUND/UNAVAILABLE. Произвольный probe
не введён, getter-once boundary сохранён.

Step-11 correction после первоначального fingerprint miss теперь при свежем
current drift повторно читает тот же fingerprint до выбора STALE. Валидный
ACCEPTED winner замораживается как REPLAYED под loser requestId; miss даёт stale,
а malformed/unavailable — persistence failure. Возврат выполняется до ID
allocation и finalize, с обычным stage/stream cleanup и без loser terminal,
audit, event или content lease. На unchanged-current path дополнительного lookup
нет. Existing post-CAS race classification не изменён.

`AssignmentOrderOriginalFreshConnection::host` отклоняет exact
ASCII-case-insensitive девятибайтовый `localhost`. И worker, и fresh factory
вызывают этот общий validator до `password()`, затем лишь после password read
создают mysqli connection. Host не переписывается; socket/fallback/protocol
option не добавлены. Public Unix-listener regression подтверждает ноль socket
connections после исправления, а direct mysqli control подтверждает
чувствительность listener. Явный `127.0.0.1` и прежние IPv4/hostname/canonical
IPv6 controls остаются рабочими.

Ранее draft-only fresh-wiring assessment проверен по actual source. Factory
construction passive; `open()` создаёт новый native mysqli, выставляет utf8mb4,
закрывает частично открытое соединение на unavailable и не раскрывает exception
или credential. Reader допускает один read, владеет read-only transaction,
использует request-key locking barrier с повторной проверкой miss, затем полный
RR backing snapshot для FOUND, и кеширует единственный close result. Recovery
копирует и валидирует result до close, закрывает reader до lease release, не
открывает второй reader и изолирует close diagnostic. Degraded production
`create` сохраняет совместимость, `createRecoveryReady` требует явный provider;
worker связывает real writer и отдельный lazy fresh provider с раздельными
clock objects и safe-log-first ordering.

Нового публичного state-changing seam, DDL owner, socket selector или mutator не
появилось. Все production файлы AssignmentOrderOriginal остаются короче 150
строк; architecture baseline не вырос. Exact fixture amendments меняют только
canonical temporary path. Isolated race oracle удаляет только невозможный
pre-finalize lease diagnostic; отдельный real post-finalize different-PDF oracle
по-прежнему требует ровно один release diagnostic и orphan ownership.

Exact changed production hashes:

```text
369aa8ae11bd072cfcc7347289bd4b432d7e0112fd866e7f99802b5cdcf3edfb  app/AssignmentOrderOriginal/AssignmentOrderOriginalCandidate.php
dec29f8bf03cc072391ffbfddb6221c8be153d64d6306e583dea798eafeb1b35  app/AssignmentOrderOriginal/AssignmentOrderOriginalFreshConnection.php
d859908ca12452f9a1cd0603d31bf9fa211de3e8500d9c6c352478d44058abc4  app/AssignmentOrderOriginal/AssignmentOrderOriginalLineageSnapshot.php
891ed5ac0ea98706e563a3a50b99e1353c470a271f595eb98f00114c6b8a9e89  app/AssignmentOrderOriginal/AssignmentOrderOriginalService.php
```

Fresh wiring hashes retained at the reviewed SHA:

```text
9a6382d6edda86ee16562da44ee448f900516d08e005506c23c85e6012468f32  app/AssignmentOrderOriginal/MariaDbOriginalFreshTerminalReaderFactory.php
ca1001369b868a26afd1ea27145725299cdb349498850e79c83e3afa48ca4a71  app/AssignmentOrderOriginal/MariaDbOriginalFreshTerminalReader.php
6410ed6fad624c7dd2569fb289582e650b3d01c3f90e53dfb83a36911c1c3122  app/AssignmentOrderOriginal/AssignmentOrderOriginalFreshRecovery.php
2004b9b6ecc4701c6de4ea5eee805a9001034515e426924b515ab328284997c9  app/AssignmentOrderOriginal/AssignmentOrderOriginalDependencies.php
3d1da61cff4b0a668996cde264e67868546b8a85f6af81278360514303ea547c  app/AssignmentOrderOriginal/ProductionAssignmentOrderOriginalFactory.php
43d9b094289a9a7f6ea66c1871ef483de99c873147a3d46fc93eb4e5a177732a  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
```

## Verification evidence

Immutable archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-data-corrected-green-lw1ys4ll`.

```text
4574939953af5144a36b3d7ab7f39c83383646cdb703acada87749a630fedee1  evidence.json
109b66421206ef930492e86d4e41b16668096dde1852d8ced8ec3a038a29fcd2  supplement-evidence.json
```

Оба manifest имеют `complete=true`, одинаковые before/after HEAD
`4ed122cac564ae22c4505d3799a1059b04fb64a9` и пустые before/after status.
Основной 47-command run сохранил 43 PASS и 4 FAIL. Все 39 original-specific
scripts, включая старые worker races и новый TCP sensor, а также architecture,
unit, lint и strict OpenSpec прошли. Три supporting scripts упали до поведения
на DB authentication, потому что runner не передал прежний synthetic env;
supplement повторил ровно эти три на том же clean SHA с прежним env, и все
прошли. Четвёртый failure — broad `git diff --check` на trailing whitespace в
immutable historical patch и terminal blank line в утверждённом Gate3 review;
scoped app/tests/OpenSpec diff в supplement прошёл.

Эта cumulative evidence достаточна для данного bounded Gate 5: она сохраняет
ошибки setup/широкого документационного diff буквально и не описывает основной
run как полностью зелёный. Неизменённые 751-case evidence предыдущего review
повторно не запускались без причины; затронутые и regression scripts входят в
текущий набор.

## Verdict boundary

**APPROVED** закрывает DATA-INTEGRITY task 5.9 на exact SHA
`4ed122cac564ae22c4505d3799a1059b04fb64a9`. Это не combined Original-command
approval и не `make verify`/`VERIFY_OK`. Denial/audit-only failures, maintenance,
public declaration parity, selection, launch wiring/readiness, deployment,
restart и golden path остаются отдельными незакрытыми gates.
