# RUNTIME-READINESS-LOAD-001 — Gate 3 test review

- Reviewer: `/root/gate3_readiness` (independent; did not author the specification or tests)
- Candidate source reviewed: `b8370070aea7b83231e205fd2a4dc9f742c2fa74f826bbca94cb772f4c4c75f2`
- Base: `3c242f34e8f30986f1b8354c4ef947a4c63936dc`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T001031Z-061050f9a6/package.json`
- Contract: `specs/RUNTIME-READINESS-LOAD-001.md` (`git hash-object`: `c8990e1cdd21b577b8060a45e84432ec5d304b35`)
- Primary RED test: `tests/Runtime/runtime_readiness_load_001_test.php` (`git hash-object`: `5c0617c5ae18abb3ff2fc6c49dcc54bb1e59426b`)
- Adjacent packaging test: `tests/Runtime/production_runtime_contract_001_test.php` (`git hash-object`: `0fd3c7c830cc358a14c8cebc22420701841eb1e6`)
- Sensitivity: persistence/schema frontier plus deployment admission and public health availability; fail-closed behavior is required.
- Verdict: **CHANGES_REQUESTED**

## Findings

1. **The public and deployment seams are not exercised.** The new test invokes
   `RuntimeReadiness` methods directly. It does not execute
   `bin/fmonitor2-runtime-check.php`, Compose's migrate/startup-check/php dependency
   chain, or `GET|HEAD /health/live` and `/health/ready`. The unchanged adjacent
   packaging test only proves the pre-existing generic migrate service and FPM
   wiring; it contains no assertion for the new one-shot startup-check, dependency
   admission, exact response/status preservation, HEAD behavior, or startup without
   an HTTP readiness cycle. A production implementation could pass the class-level
   assertions while leaving deployment admission and public health behavior wrong.

2. **The central bounded-load claim has no executable oracle.** Eight sequential
   calls are made, but the test neither counts SQL commands nor observes which SQL
   runs. It does not independently cap the command count, show that it is invariant
   with catalogue size, reject fingerprint queries/DDL/advisory locks, or compare
   temporary-table counters. Consequently an implementation that performs the old
   full fingerprint walk and then validates an attestation could pass. The required
   sequential and four-concurrent measurements, background control interval, and
   separate startup deep-check cost are absent.

3. **The attestation security and replacement contract is largely untested.** There
   are no cases for atomic publication, mode `0600`, current UID/GID, one hard link,
   symlink/non-regular-file rejection, malformed/incomplete content, schema-version
   mismatch, prefix mismatch, marker corruption/change, format-version mismatch, or
   credential non-disclosure. There is also no failure-after-prior-success case
   proving that a failed repeat/update cannot leave stale evidence applicable, and
   no interrupted-write/concurrent-publication case. Checking unchanged contents,
   mtime and inode after successful steady probes is useful read-only coverage but
   does not cover these acceptance boundaries.

4. **The failure and concurrency matrix is incomplete.** Missing marker, database
   mismatch, build mismatch, and initial missing attestation are represented, but
   DB loss after startup, incompatible/missing schema, failed/incomplete migration,
   failed deep check, rollback state, prefix/schema mismatch, repeat/update, four
   concurrent probes, liveness during DB loss, and proof that ERP/Bitrix/SMTP are
   not called are absent. The test also does not snapshot database/filesystem state
   around rejected probes, so it cannot reject repair, DDL, marker writes, or other
   unintended mutation.

5. **RED evidence is too shallow for the proposed suite.** The exact prepared record
   `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790035796070917000-4b308f87716645e1a24465079f795362.json`
   is source-bound and fails for the intended missing catalogue v33 marker: expected
   schema version `33`, actual `32`. That proves one missing prerequisite, but the
   test exits at line 27 before any readiness/attestation assertion. There is no
   independently observed RED for the new public startup chain, bounded query count,
   security checks, mismatch matrix, or concurrency. Those branches could be broken
   setup or vacuous and the current evidence would not reveal it.

## What is acceptable already

The contract is traceable to the active goal and OpenSpec delta, names the existing
public health and runtime-check seams, and clearly separates startup deep checking
from steady readiness. The primary test uses unique database names and a unique
temporary state root with `finally` cleanup, and its expected catalogue increment,
stable fail-closed reason, DB/build mismatch, and steady-file immutability checks
are deterministic. These strengths do not close the missing sensitive boundaries.

## Required changes before rereview

- Add focused executable coverage through the CLI, Compose/deployment declaration,
  and real GET/HEAD health seams, including unchanged JSON/status and DB-independent
  liveness.
- Add an independent SQL-observation oracle for fixed steady cost/no fingerprints,
  sequential plus four-concurrent probes, and the specified counter/background
  measurement discipline.
- Complete the attestation security, mismatch, failed replacement, read-only
  snapshots, migration/deep-check failure, and concurrency matrix above.
- Capture source-bound RED evidence that reaches each materially distinct fixture
  family (or split the suite so one early missing migration cannot mask all later
  branches).

Production implementation is not authorized by this review.

---

## Independent rereview — corrected Gate 3 candidate

- Reviewer: `/root/gate3_readiness` (same independent reviewer; no specification,
  test, or production authorship)
- Candidate source reviewed: `0a6cbd3eb68ed63ab843a01ffd9f1c304d7fbaab9d0f6fb8ca39e02d73f6c81b`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T001855Z-2173931a1d/package.json`
- Primary test SHA-256: `cb3f1a2788c2725a998b3a805a1933077abe72bfc806e91ab9767270c983ffb4`
- Packaging test SHA-256: `4348197177c0a306a6417ae211c8a033cacec65f63ac5a61a3a53215cf0f5523`
- Verdict: **APPROVED**

The exact delta from the refused candidate changes only the verification input,
the two root-authored tests, and this preserved review record. The specification
is unchanged. Both corrected tests pass `php -l`; `git diff --check` is clean.

### Disposition of prior findings

1. **Public/deployment seams — resolved for Gate 3.** The primary test now invokes
   the public `bin/fmonitor2-runtime-check.php` process and checks exact stdout,
   stderr and exit status for success and failed repeat. The packaging test requires
   one `startup-check` service, canonical CLI invocation, successful migrate
   dependency, php's `service_completed_successfully` dependency, deterministic
   image build identity, and no HTTP bootstrap cycle. Existing registered runtime
   HTTP tests retain exact GET/HEAD JSON/status, liveness during DB failure, and
   route behavior; they remain regression/CI obligations rather than being copied
   into this focused test.

2. **Bounded-load oracle — resolved for pre-implementation Gate 3, with operational
   measurement explicitly deferred.** The corrected suite makes sequential and
   four-process concurrent steady probes, proves that neither changes or republishes
   the attestation, and statically rejects the known full-schema call from the
   regular readiness facade. The contract's SQL-command, temporary-table,
   background-control and elapsed-time comparison remains the explicit isolated
   evidence task in OpenSpec task 3.1. Gate 3 does not hardcode a machine-specific
   latency percentile. Final review must treat missing measurement evidence as
   `UNKNOWN`, not infer bounded load from these tests alone.

3. **Attestation security/replacement — resolved.** The test independently checks
   regular non-symlink type, mode `0600`, effective UID/GID, one hard link,
   incomplete content rejection, symlink rejection, stable file bytes/mtime/inode
   across sequential and concurrent probes, and invalidation of prior success after
   a failed repeat deep check. Exact build, prefix and database bindings are separate
   deterministic cases. The catalogue owns the schema-version frontier; malformed
   content also prevents a format-agnostic acceptance path.

4. **Failure/concurrency matrix — resolved in combination with existing owners.**
   The focused test covers missing startup evidence, build/prefix/database mismatch,
   malformed and substituted evidence, four concurrent probes, and a full-check
   schema failure after success. Existing selected runtime/schema/recovery tests and
   registered HTTP/Compose tests own live DB loss, storage, liveness, canonical
   migration failure, and public response preservation. This slice has no new
   integration client seam; the bounded readiness implementation is constrained to
   runtime/storage/DB boundaries and the final architecture/code review must reject
   an added ERP, Bitrix, SMTP, DDL, or advisory-lock dependency.

5. **RED reachability — resolved.** Exact-source record
   `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790036295271159000-7f89c6925386432dba305ed8e56c0c0b.json`
   migrates successfully, supplies an isolated marker only when the pre-change
   catalogue lacks v33, and then fails at the intended behavior: `SELECT 1` without
   startup evidence is accepted instead of raising `STARTUP_NOT_READY`. The separate
   exact-source packaging record
   `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790036295271172000-8e4e287288b04290adaddce1767ed1fa.json`
   fails because the one-shot Compose startup-check is absent. Thus fixture setup no
   longer masks both principal behavior families.

The corrected tests are deterministic and isolated through unique databases and a
unique private state root with `finally` cleanup. Expected values come from the
published contract and existing public CLI/health contracts, not a proposed
implementation. Gate 3 authorizes the separate executor to implement against this
candidate. It does not approve production code, substitute for task 3.1 operational
measurements, final review, or exact-source CI.
