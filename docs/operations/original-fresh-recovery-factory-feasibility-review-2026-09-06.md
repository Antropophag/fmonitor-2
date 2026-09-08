# Original command — fresh recovery factory feasibility review

Дата: 2026-09-06.  
Reviewer task: `/root/selection_v04_readiness`.  
Reviewed HEAD: `d7ed54d03041605200887c607ce6b3ce81f579be`.  
Verdict: **TECHNICALLY VIABLE WITH EXPLICIT DEGRADED/READY COMPOSITION**.

Это bounded read-only review следующего data-integrity Gate 1. Код, tests и
specifications не изменялись. Review не разрешает считать текущую production
factory recovery-ready и не предлагает извлекать credentials из `mysqli`,
клонировать connection или выбирать secrets/env/loggers внутри command.

## Exact reviewed hashes

```text
de9622d1d7691330fe905b0cfefc49b8ad4f7985489b6b4fab51d9e750a2cd52  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
0a9c81f0cd173ae1e75262bcae5e3ae88b6d662476eb284785564b5008a4456c  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php
ce1e072b08705f6347de23e2867ee53c8e774c528b3abf5f1c5b121d9a21bc3c  app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php
014af5a9b72ab93d7e03e9d3dbb1da208b22b28e5bf9c40bead0532ac60e74a8  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
952a302ac7d27ed009af9e3331780617cff26a0e045a96edc4815f77d2beb714  app/AssignmentOrderOriginal/AssignmentOrderOriginalVerificationWorkerBootstrap.php
```

## Current constraint

`ProductionAssignmentOrderOriginalFactory::create(mysqli $db, Config $c)` owns
only a caller-supplied live connection plus storage/log configuration. `mysqli`
does not expose the original password or a complete trustworthy DSN suitable for
opening another authenticated connection. Reading connection metadata cannot
recover credentials and would not prove the same trusted target.

`AssignmentOrderOriginalMariaDbRepository` stores that one connection. Current
`AssignmentOrderOriginalCommitProtocol::recover()` calls the ordinary
`repository->findTerminalRequest()` after `OUTCOME_UNKNOWN`, so production
recovery may reuse a connection whose commit acknowledgement was lost. This does
not satisfy the parent's genuine fresh-connection requirement.

The valid design boundary is dependency injection: a caller that already owns
trusted connection configuration supplies a lazy provider capable of opening a
new read-only connection. The write connection remains caller-owned; the fresh
reader owns and closes only the connection it opens.

## Recommended bounded port contract

Use an explicit recovery port rather than hidden “next lookup is fresh” state:

```php
interface AssignmentOrderOriginalFreshTerminalReaderFactory
{
    public function open(): AssignmentOrderOriginalFreshTerminalReaderOpenResult;
}

interface AssignmentOrderOriginalFreshTerminalReader
{
    public function findTerminalRequest(string $requestId):
        AssignmentOrderOriginalResultLookup;
    public function close(): AssignmentOrderOriginalFreshTerminalReaderCloseStatus;
}
```

`OpenResult` should be a closed value with exactly `opened(reader)` and
`unavailable()`. `CloseStatus` should be a closed enum `CLOSED|FAILED`. Factories
and constructors must forbid status/payload mismatches. The reader is one-shot
for the exact recovery lookup; it exposes no query, credentials, write API or
general repository.

The repository/application recovery surface must be semantically explicit, for
example either:

```php
interface AssignmentOrderOriginalFreshTerminalRecovery
{
    public function findTerminalRequestFresh(string $requestId):
        AssignmentOrderOriginalResultLookup;
}
```

implemented by the MariaDB repository through its factory, or a separate fresh
factory dependency passed to `AssignmentOrderOriginalCommitProtocol`. The
protocol must call that exact method only after `OUTCOME_UNKNOWN`/commit
Throwable. It must never call ordinary `findTerminalRequest()` and assume that a
repository flag makes the call fresh.

Exact recovery mapping remains inherited:

- open/read/validation unavailable → `PERSISTENCE_OUTCOME_UNKNOWN`;
- validated FOUND exact stored terminal → copied accepted/stored outcome;
- validated NOT_FOUND → `PERSISTENCE_FAILURE`;
- malformed result/status/data → unavailable, never NOT_FOUND;
- no second commit, allocation or recovery connection;
- reader close attempted once after the lookup before lease release.

The executable contract must decide close-failure mapping explicitly. A safe
bounded rule is that an already validated read result remains selected because a
read-only close failure does not undo it, while close failure is diagnostic only;
if the parent instead treats unconfirmed connection cleanup as unavailable, that
must be stated and tested. It cannot be left to adapter exceptions.

## Optional versus mandatory construction

An optional third parameter is sufficient for source compatibility:

```php
ProductionAssignmentOrderOriginalFactory::create(
    mysqli $write,
    AssignmentOrderOriginalProductionConfig $config,
    ?AssignmentOrderOriginalFreshTerminalReaderFactory $fresh = null,
): AssignmentOrderOriginalApplication
```

`AssignmentOrderOriginalMariaDbRepository` may likewise accept the optional
factory after its existing optional fault dependency. Absence must install a
typed unavailable provider. It must never reuse `$write` as fallback. Normal
commit, rejection and replay paths remain usable; only unknown-commit recovery
is degraded to `PERSISTENCE_OUTCOME_UNKNOWN`.

A mandatory signature is not technically necessary and would force unrelated
pure/unit and legacy construction sites to invent a provider. It also would not
by itself prove that the provider opens a genuinely new connection.

However, optional construction cannot be called launch-ready. The exact contract
must distinguish:

1. **compatibility/degraded construction** — two arguments accepted, missing
   provider, unknown recovery always unavailable;
2. **recovery-ready production wiring** — explicit provider required and checked
   by the later build/readiness contract before publication/startup.

If this distinction cannot be made observable in readiness, use a separate
mandatory `createRecoveryReady(...)` production entrypoint while retaining the
two-argument `create(...)` only for compatibility tests. Silently starting the
launch contour through two-argument construction is forbidden.

The provider object itself must be lazy and perform no connection/file/secret
access during argument construction. Production factory retains its existing
ordering: safe-log path/open validation first, then private-root/prefix checks,
then dependency composition. Invalid safe-log tests must prove provider open
calls zero and caller DB calls zero. No provider validation may move ahead of the
approved safe-log boundary.

## Composition sites requiring exact disposition

### Production factory

`app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php:36` constructs
the production repository. It must pass the supplied provider or explicit
unavailable provider. Current two-argument call sites are confined to
`assignment_order_original_production_boundary_001_test.php`, including invalid
safe-log ordering probes. Those calls should remain compatibility cases; add one
explicit provider case proving recovery-ready composition and genuine fresh read.

No live portal call to `ProductionAssignmentOrderOriginalFactory::create` was
found in repository source. This absence is not launch wiring proof; later HTTP/
bootstrap integration must select the ready construction explicitly.

### Verification worker

`app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php:95` constructs
`AssignmentOrderOriginalMariaDbRepository` directly. Worker config already owns
the DSN, username and password-file path and may create a lazy provider explicitly
from those trusted verified inputs. The password content must follow existing
worker ordering and must not be copied into result/log/config output.

Worker proof must show a second server connection identity, same configured
database/prefix, utf8mb4, read-only lookup owner, close once and no reuse of the
write connection. Existing unknown-found/not-found/unavailable scripts must be
rewired to this provider rather than faulting the write repository's next read.

### Direct repository tests

`assignment_order_original_gate5_mariadb_red_001_test.php:53` directly constructs
the repository against a missing prefix. It can retain absent-provider degraded
construction unless the test claims fresh recovery. Pure lifecycle fixtures use
interface repositories and need no MariaDB provider.

### Production boundary and owner ordering tests

All current `ProductionAssignmentOrderOriginalFactory::create` calls in
`assignment_order_original_production_boundary_001_test.php` use two arguments.
Keep the constructor/factory reflection and invalid safe-log cases compatible.
Add provider call-count sentinels to prove missing/invalid safe-log and invalid
private-root/prefix paths do not open a fresh connection.

## Genuine-new-connection proof obligations

Gate 2/3 must use approved synthetic data and public composition. Required
observations:

- provider open count exactly one only after typed unknown commit;
- fresh MariaDB connection has a different server connection ID from the write
  connection and same expected server/database identity;
- lookup uses exact prefix/request and read-only adapter; no DDL/DML;
- durable FOUND, reliable NOT_FOUND and unavailable each produce inherited
  external results and one reader close;
- write connection made unusable after commit acknowledgement loss, while fresh
  FOUND still resolves accepted evidence;
- missing provider never touches the write connection for recovery and returns
  outcome unknown;
- provider Throwable, open unavailable, query failure, malformed stored row and
  close failure follow exact mappings;
- lease remains held through the complete fresh lookup/validation/close policy
  and is released once afterward;
- no credential/DSN/path/SQL/exception leaks and no retry/open-two behavior;
- ordinary commits, terminal replay and conflicts never open a fresh reader.

Connection-ID difference is useful evidence but not sufficient alone. Provider
construction must be tied to the same trusted configuration/server/database and
the stored result must pass the separate exact data-integrity validator before
FOUND is accepted.

## Authorization-denial audit timestamp ambiguity

The parent requires a valid-shape authorization denial to persist one terminal
request and safe attempt audit so a later authorized invocation with the same
request ID replays the denial. Current service performs authorization before its
normal clock acquisition and calls `finish()` with the sentinel
`1970-01-01T00:00:00Z`; `finish()` explicitly skips terminal attempt persistence
for that sentinel. Thus production denial currently returns
`AUTHORIZATION_DENIED` without the required request/audit.

This affects data-integrity expectations because fresh/ordinary terminal lookup
cannot later find the promised denial. The next exact contract must resolve
timestamp ownership without moving confidential lookup before authorization:

- after typed DENIED, acquire one validated clock instant solely for the safe
  denial terminal/audit; then commit it through the existing attempt seam; or
- define a repository-owned trusted attempt timestamp and exact failure mapping.

Clock unavailable cannot yield a falsely audited denial. The exact precedence and
external result on denial-clock/audit failure must be pinned from existing parent
policy. This is a technical completion of already approved audit behavior, not a
new product decision. It should not be hidden inside the fresh-reader provider.

## Disposition

The optional typed fresh-reader provider is feasible and preferable to forbidden
credential extraction or connection reuse. A mandatory change to every factory
call is unnecessary if degraded two-argument construction is explicit and later
launch readiness rejects it. The Gate 1 contract must expose the fresh recovery
operation, provider/reader result closure, close semantics, wiring modes and
proof obligations above. It must also reconcile denial terminal/audit timestamp
ownership before claiming complete request-result data integrity.
