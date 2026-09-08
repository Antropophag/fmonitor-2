# Scoped code review draft: DATA-INTEGRITY v0.6 fresh native recovery and wiring

Date: 2026-09-06.

Reviewer: separately tasked agent `/root/selection_v04_readiness`.

Reviewed implementation commit: `94a17bfef8175669a2265ebd03a33e77c143bfee`.

Baseline: `8350038`.

Verdict: **CHANGES_REQUESTED**.

This draft is outside the repository while the shared regression runner is
active. Reviewer authored neither implementation nor tests. Scope is limited to
fresh native reader/factory, public recovery composition, production/worker
wiring and the supporting native commit path. Other DATA-INTEGRITY components
and all remaining declaration, storage, maintenance, selection and combined
launch gates remain separate.

## Exact reviewed hashes

```text
c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd  specs/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001.md
4f5be0695a95fc4d912261647579ad4fb214df5dd0da90edeb6f38fe11f6b3fc  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
80cc638fb916738dc1c990d434857e4dd6b5399241c85121288b4667ff2f39fc  app/AssignmentOrderOriginal/AssignmentOrderOriginalFreshConnection.php
ca1001369b868a26afd1ea27145725299cdb349498850e79c83e3afa48ca4a71  app/AssignmentOrderOriginal/MariaDbOriginalFreshTerminalReader.php
9a6382d6edda86ee16562da44ee448f900516d08e005506c23c85e6012468f32  app/AssignmentOrderOriginal/MariaDbOriginalFreshTerminalReaderFactory.php
6410ed6fad624c7dd2569fb289582e650b3d01c3f90e53dfb83a36911c1c3122  app/AssignmentOrderOriginal/AssignmentOrderOriginalFreshRecovery.php
a47c52b06d75831bc6d2d1b4617f4bf889f2a826da86229f2efe4e45a60b8438  app/AssignmentOrderOriginal/AssignmentOrderOriginalDataContracts.php
2004b9b6ecc4701c6de4ea5eee805a9001034515e426924b515ab328284997c9  app/AssignmentOrderOriginal/AssignmentOrderOriginalDependencies.php
3d1da61cff4b0a668996cde264e67868546b8a85f6af81278360514303ea547c  app/AssignmentOrderOriginal/ProductionAssignmentOrderOriginalFactory.php
ffc749e28d07a2d46b2ed223b85a33ed0d1ced3bb9565782a12e60e819d5b717  app/AssignmentOrderOriginal/AssignmentOrderOriginalWorkerRecoveryObserver.php
43d9b094289a9a7f6ea66c1871ef483de99c873147a3d46fc93eb4e5a177732a  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
2878617d8ab09867e7336e410e995288d4bfeaf3d70d387178f7c1f06b4fb3e2  app/AssignmentOrderOriginal/MariaDbOriginalRepositoryWrites.php
```

Approved tests/reviews:

```text
e8c8d8db891f3ee739ce88e58f6795db9f0527289823a00c2ff944cad39bf65c  tests/InstallationProcess/assignment_order_original_data_mariadb_fresh_001_test.php
ed9a50db4d5c156168f9c89451cc8cbae10014fb585093260a4633c918f9708e  tests/InstallationProcess/assignment_order_original_data_recovery_001_test.php
ea4bde48b6ac265b8e91d166feec723dcfb911ab821517a4a89a1ab22d8a10d0  tests/InstallationProcess/assignment_order_original_data_worker_001_test.php
f99987d32a1cfe8d0186007fa350dd1dd3dcbf0fab6363f98ad9ed42a8242e5e  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-DATA-MARIADB-FRESH-001-v1.md
eecd3408cb1692238f08e293ed272f6eb89f96b92cd3598c799d7bc5161f939d  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-DATA-RECOVERY-001-v1.md
0f5ac2c2c38a30699bb5d3069a0c5cfb2a150880f1c5734f16db091856b34fcf  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-DATA-WORKER-001-v1.md
```

## Blocking finding

### P0 — admitted `localhost` can select a Unix socket and ignore the configured port

`AssignmentOrderOriginalFreshConnection::host()` accepts every parent hostname
token matching the broad ASCII grammar, including literal `localhost`. The fresh
factory passes that value and the configured port directly to
`mysqli::real_connect()`.

PHP mysqli treats `localhost` specially and may select a local Unix-domain socket
rather than TCP. In that mode the port argument is not the endpoint selector.
The parent connection contract explicitly forbids Unix sockets and requires the
byte-exact mysqli host/user/password/database/port tuple. Therefore one admitted
configuration has behavior that contradicts the contract.

The same issue affects worker writer construction because the worker parses the
same host grammar and calls native mysqli with the returned `localhost` value.
Fresh-reader new connection ID and successful read do not prove TCP/port usage.

Existing approved tests use the fixture host, normally `127.0.0.1`. They reject
socket paths and `p:` prefixes but do not exercise `localhost` with an available
default socket and deliberately wrong TCP port. Passing 751 new cases on one IP
host does not close this admitted branch.

Local extension inspection confirms there is no exposed protocol-selection
option in this runtime. Available relevant mysqli constants include connect/read
timeouts and other MYSQLI_OPT values, but no `MYSQLI_OPT_PROTOCOL`. The reflected
`real_connect` signature accepts hostname, username, password, database, port,
socket and flags; passing null for socket does not override mysqli's documented
special treatment of `localhost`. `php --ri mysqli` reports mysqlnd 8.5.10 and a
configured default socket `/tmp/mysql.sock`. Therefore this implementation
cannot force TCP for the admitted literal without changing/rejecting the host
token; the problem is not cured by the current call shape.

Official php-src PHP-8.5 mysqlnd source makes the special token exact: its
`get_scheme` compares host length with nine-byte `localhost` and then uses
case-insensitive `strncasecmp`. Every ASCII case variant of exact `localhost`
selects `unix://`; longer or different names take the TCP branch. The correction
must therefore reject exact ASCII-case-insensitive `localhost`, not only the
lowercase spelling. Primary source:
`https://github.com/php/php-src/blob/PHP-8.5/ext/mysqlnd/mysqlnd_connection.c#L481-L535`.

Required correction before scoped Gate 5 approval:

1. Amend the exact parent/data-integrity connection grammar to exclude
   `localhost` and any other mysqli-special host token that selects a forbidden
   transport, then obtain the required specification/test review; or provide an
   exact supported way to force TCP while preserving the approved host/port
   semantics. Do not rewrite host through ambient DNS or extract connection
   credentials.
2. Apply the same validation to fresh and worker writer connection construction.
3. Add a public factory/worker control with `databaseHost=localhost`, an available
   local socket and a deliberately wrong TCP port. It must not successfully
   connect through the socket. If the chosen contract rejects this token, expect
   typed fresh UNAVAILABLE and fixed worker configuration failure before password
   content/DB access according to the exact ordering amendment.
4. Gate 5 source review must verify no socket parameter, persistent prefix,
   fallback or host rewrite is introduced.

This is a technical transport-identity correction, not a product decision or API
expansion.

## Existing worker fixture compatibility

Three older worker regressions build control/password paths directly from
`sys_get_temp_dir()`. On Darwin that value may use `/var/...` while `realpath`
returns `/private/var/...`. The new password validator correctly requires the
canonical path and therefore rejects those old fixture strings before DB access.

The compatibility correction belongs in test setup: after creating the exact
owned control directory, resolve and retain its canonical `realpath`, then derive
password/private/log paths from that canonical root. Preserve bounded cleanup by
validating the same resolved identity. Do not weaken the production validator or
add a `/var` alias exception merely to preserve noncanonical fixture input.

This old-test patch requires its own exact hash and independent review. It is not
a production finding and does not alter the `localhost` blocker.

## Conforming implementation areas

### Closed public recovery types — PASS

Fresh open result has only OPENED/non-null and UNAVAILABLE/null factories, private
clone and fixed nonserialization. Dependencies preserves the existing 12
arguments and adds one nullable trailing factory, initializing its readonly
property once or using an explicit unavailable provider. Degraded construction
cannot borrow the writer.

### Lazy configuration and credential ownership — PASS except host finding

Factory construction is passive. Host/config scalar validation precedes password
content and connection access. Password path is absolute/canonical/outside the
repository, exact regular 0600, root/current-user owned and checked by
lstat/open/fstat/lstat identity before returning bounded printable bytes. A
partially opened mysqli is closed once before UNAVAILABLE.

No password, path, SQL or exception is returned or logged. The host transport
special case above is the only blocking connection-grammar finding identified.

### Genuine fresh one-shot reader — PASS

Factory opens a new native mysqli and sets utf8mb4. Reader permits one terminal
lookup and one cached close; second read or read-after-close returns UNAVAILABLE
without SQL, and repeated close performs no native close. Observer failure still
attempts native close and caches FAILED.

The READ COMMITTED locking read observes or waits on an exact pending request
record. An empty barrier is verified by a second request read before transaction
release; an appearing row becomes conservative UNAVAILABLE rather than false
absence. A present barrier is released, then full request/revision/root/event/
audit backing is validated in an owned consistent snapshot. Query-linked orphan
facts therefore cannot be hidden as NOT_FOUND.

Result objects are frozen before close. Close failure preserves the selected
validated read and triggers one exact phase-only safe diagnostic through the
existing correlation envelope.

### Recovery protocol — PASS

Unknown accepted and authorized-attempt outcomes use only the injected fresh
factory. Open/read/status/result getters are captured once; impossible
status/payload or stored-data validation maps outcome unknown. Validated FOUND,
reliable NOTFOUND and unavailable retain their exact public results. Reader close
occurs in finally before lease release and cannot replace the selected result.
There is no second open, commit, allocation or clock.

### Production factory and separate clocks — PASS

Production `create` remains degraded when no provider is supplied;
`createRecoveryReady` requires one. Safe-log owner acquisition occurs before
private-root/prefix/dependency composition. The application clock and storage
clock are separate instances. No runtime DDL or new command seam is introduced.

Launch readiness still must reject degraded wiring and probe actual target
availability; a non-null provider alone is not claimed ready.

### Worker ordering and fault scripts — PASS

Worker opens the existing safe-log owner before password-file content or writer/
fresh DB connection. Fresh config is passive. It then uses separate fixed clock
instances for application and storage.

Unknown-found/not-found/unavailable scripts route through the new fresh owner.
The recovery observer makes only the approved fresh read unavailable and does not
mutate the ordinary writer repository. Safe-log-first missing-path controls and
fixed failure transport remain intact.

The distinct-PDF after-finalize race reaches the real repository write guard:
both candidates pass normal step 11 and finalize under different digest leases;
A commits; B enters `commitAccepted`, the write guard detects current-pointer CAS
loss, confirmed rollback returns CONFLICT, and application rereads stale current.
The losing lease is released once, its release failure emits commit_conflict, and
its unreferenced private blob remains an allowed orphan without domain facts.
The same-PDF race remains a pre-finalize stale/no-extra-blob control.

### Native commit/rollback classification — PASS

Repository validates DTOs before transaction, owns READ COMMITTED writes and
tracks commit attempted versus native success. Post-native observer failure and
commit acknowledgement uncertainty return OUTCOME_UNKNOWN without second commit.
Rollback observer/native failure prevents false confirmed rollback. Worker fault
scripts use the same path rather than a separate fake repository.

## Verification state

The completed parent evidence manifest has SHA256
`7616880e290a58c0fe5671c80998a498f087b9e89249eb39e2afd6c755c5283c`
and records identical before/after HEAD
`94a17bfef8175669a2265ebd03a33e77c143bfee` with clean status. All 751 new cases,
supporting checks, architecture, unit, lint, strict OpenSpec and diff check pass.

Exactly three older worker regressions fail:

```text
assignment_order_original_lease_race_001_test.php
assignment_order_original_worker_post_finalize_negative_001_test.php
assignment_order_original_worker_transport_001_test.php
```

Their noncanonical Darwin `/var` password paths are the fixture compatibility
issue described above. Regression completion does not cure the separate admitted
localhost transport branch.

## Scope disposition

No other scoped source finding was identified. Because the `localhost` branch
violates the explicit no-Unix-socket connection contract, the frozen
`94a17bfef8175669a2265ebd03a33e77c143bfee` implementation cannot receive scoped
Gate 5 approval. Later regression completion cannot cure this untested admitted
transport branch.

This verdict does not reopen unrelated DATA components and does not approve or
reject remaining public declaration, storage/maintenance, selection, combined
command, deployment or launch gates.
