# DURABLE-JOBS-FOUNDATION-001 — bounded Gate 5 review

- Reviewer: `/root/runtime_review`
- Fixed point: `839001b427ccff851dc0332cfe2d731e207f2bee`
- Scope: queue runtime, composed six-table v23 migration and Jobs architecture guard
- Verdict: **APPROVED**

## Standards

No blocking standards finding. SQL remains in `MariaDb*` adapters, DDL remains in
`InstallationProcess`, and the guard permits DML only for the six reviewed Jobs
families while rejecting application SQL, native DDL, foreign fact-family literals
and Pilot/Rapid dependencies. The large literal schema definition is split into
queue and delivery definitions so it stays below the existing hotspot limit; the
test manifests remain independent oracles rather than production inputs.

## Spec

The queue validates closed commands and canonical bounded JSON before transactions,
uses an empty production handler registry, returns the reviewed closed result
shapes, obtains one UTC instant per operation, and performs short READ COMMITTED
transactions with a fixed two-second InnoDB lock wait. Enqueue replay/conflict,
stable claim order, lease token CAS, heartbeat, completion, retry delays and fifth
attempt expiry/dead transitions match the approved contract. Handler/network work
does not occur inside these transactions.

`JobsSchemaMigration` preflights all six exact table fingerprints before DDL,
creates them in dependency order, exposes read-only readiness, and is registered as
canonical migration 23. The narrowed outbox status set
`pending|delivered|dead` is coherent with the reviewed generic queue design: lease
and retry state belongs to the `outbox.dispatch` job rather than the intent row.

Reviewed source hashes include:

```text
dcde9b4c3b9f38262780efbc819b1967f538726a6adc85ee22891e1974ad41a7  app/Jobs/MariaDbJobQueue.php
d99f530970082ccf1d2e9383bdb9c87e5d3d2940a5e71c237841c02a1aa00b17  app/Jobs/MariaDbJobsSession.php
247bf286a9eddc2754529776bde8f9097f4084bfccee9a2d23b5c7789c0064c1  app/Jobs/JobValues.php
171b25165413ed3feaff93522244b257c71b3c163acb8f29b8f5c1e10d8d311d  app/InstallationProcess/JobsSchemaMigration.php
552cc790f0a7ebea014aeaac8453789918b6891b0baf045e4e934f420a463db6  app/InstallationProcess/MariaDbJobsSchemaFingerprint.php
162aeb4ebf733f52d20dfd682b4bcf4abe68bf8daf9fea3cf0bdccb91ea9694e  app/InstallationProcess/JobsQueueDefinitionSchemaMigration.php
d871a2fd4cc3a56167c9549dc33831a33110caceaad58ac22922cb527ea2b26c  app/InstallationProcess/JobsDeliveryDefinitionSchemaMigration.php
c313c74586e0cd79d151d6323cc9ab5b61bff0240c2fad2bd9066d54d0d8182c  tools/architecture/check.py
```

Verification independently repeated:

```text
durable_queue_001_test.php: PASS
durable_queue_concurrency_001_test.php: PASS
jobs_schema_001_test.php: PASS
jobs_extensions_schema_001_test.php: PASS
architecture unit tests: 52/52 PASS
architecture check: ok=true, rules=7, errors=[]
production PHP syntax: PASS
git diff --check: PASS
```

This approval does not cover outbox, scheduler, worker, operator/health production
implementations or production handler registration. Their behavioral Gate 3 and
subsequent Gate 5 reviews remain separate.

## CHECK-literal correction addendum

The initial review found one later-demonstrated fingerprint defect: normalization
lowercased quoted CHECK literals, so uppercase status values could compare equal to
the required lowercase values under `ascii_bin`. The independently approved RED
changed only those literals, proved the binary predicate false, and observed
`isReady=true` before the correction.

Reviewed correction hashes:

```text
839eb17d55ec31fb3982dc725288b750d2ecee584f08f8a3b80829c6ad7dfa9d  app/InstallationProcess/MariaDbJobsSchemaFingerprint.php
720bf4854668fe957d4ec911e2ad50fbc88f95340d99ad942e9527e8dd3f21cc  tests/Support/jobs_schema_assertions.php
0f278caf07b333fec5bbea074770d2f1d07b45a1768ea9839deba3d8b6c42d5b  tests/Support/jobs_extensions_schema_assertions.php
3db503af23cb98e17b3dc70c677252360ee91cdb7559cbd9096db0be2ef423e5  specs/DURABLE-JOBS-SCHEMA-001.md
9461cf8d3ab273ad2e4edfa792aeebe47682d7051d2e17d1bd937f0a498af765  tests/Jobs/jobs_schema_check_literals_001_test.php
```

The parser now removes whitespace/backticks and redundant full-expression wrappers
only outside quoted strings. It lowercases SQL syntax while retaining literal bytes,
case, escapes and internal grouping. The production fingerprint and both test
oracles reject the uppercase binary-status schema, and apply preserves the complete
six-table snapshot on conflict.

Independent verification:

```text
jobs_schema_check_literals_001_test.php: PASS
jobs_schema_001_test.php: PASS
jobs_extensions_schema_001_test.php: PASS
git diff --check: PASS
```

**Addendum verdict: APPROVED.** Queue behavior and the previously reviewed schema
manifest are unchanged.

## Runtime migration-call boundary addendum

The Jobs architecture fixture added direct, imported-alias and canonical-run
migration invocation cases plus a read-only `isReady` control. The reviewed guard
reuses the existing runtime migration matcher for `app/Jobs` without a new rule or
baseline entry.

```text
bad111a5e5bae0e0febfce6319284d94848ec62fbb9e9623372f8ba5187b988f  tools/architecture/check.py
3d98afb5fa1f13c77f9c021df75670cfe575f42dbdc3af0be33a5e603be48516  tools/architecture/tests/test_jobs_native_owner.py
```

The focused fixture is GREEN, all 54 architecture unit tests pass, and the actual
architecture check remains seven rules with no errors. **Addendum verdict:
APPROVED.** Jobs runtime may read deployment readiness but cannot invoke migration
owners.

## Connection-loss characterization

```text
6b839c29530c0fb7b0ce0b10bad5bee249fd7f694e115cd493d510bdc8e4add2  tests/Jobs/durable_queue_connection_loss_001_test.php
87916d84262adb0e903e6c8792d81da1ba5328c17dc11b30431bf614ec1ec2dd  tests/Support/jobs_connection_loss_worker.php
```

A task-owned DML connection is observed by exact thread id while blocked in the
event trigger after its uncommitted job insert. Killing only that connection rolls
back job and event, and a new connection creates the same identity. A separate
event `SIGNAL` fixture returns closed `JOBS_UNAVAILABLE` and preserves the prior
rows. The supplemental test passes without production changes.
