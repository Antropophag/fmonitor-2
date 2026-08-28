# Code review: ORDER-PREPARE-002

- Reviewer: `Codex agent /root/order_prepare_002_code_review` (independent; did not author the specification, test, or production implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-27`
- Reviewed commit: `working tree / HEAD 6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ORDER-PREPARE-002.md`](../../specs/ORDER-PREPARE-002.md), version `0.1`, `APPROVED 2026-08-27`
- Approved test review: [`reviews/tests/ORDER-PREPARE-002.md`](../tests/ORDER-PREPARE-002.md)
- Production implementation: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Test adapter: [`tests/Support/InMemoryInstallationProcessEnvironment.php`](../../tests/Support/InMemoryInstallationProcessEnvironment.php)
- Verdict: `APPROVED`

## Verification evidence

Commands run independently from `/home/antropophag/code/fmonitor-2`:

```text
php tests/InstallationProcess/order_prepare_002_test.php
for test_file in tests/InstallationProcess/order_prepare_001{,_b,_c,_d,_e,_f,_g,_h}_test.php; do php "$test_file" || exit 1; done
php -l app/InstallationProcess/InstallationProcess.php
php -l tests/Support/InMemoryInstallationProcessEnvironment.php
php -l tests/InstallationProcess/order_prepare_002_test.php
git diff --check
```

Observed output:

```text
PASS ORDER-PREPARE-002 example A
PASS ORDER-PREPARE-001 example A
PASS ORDER-PREPARE-001 example B
PASS ORDER-PREPARE-001 example C
PASS ORDER-PREPARE-001 example D
PASS ORDER-PREPARE-001-E audit projection
PASS ORDER-PREPARE-001-F audit projection
PASS ORDER-PREPARE-001-G combined audit projection
PASS ORDER-PREPARE-001-H security audit
No syntax errors detected in app/InstallationProcess/InstallationProcess.php
No syntax errors detected in tests/Support/InMemoryInstallationProcessEnvironment.php
No syntax errors detected in tests/InstallationProcess/order_prepare_002_test.php
```

Every command exited `0`; `git diff --check` produced no output.

## Findings

- **Specification conformance:** for approved example A, `prepareOrder(...)` returns the exact accepted result and persists the exact observable version, order/installer/engineer snapshots, preliminary assignments, task closure, closed installation/checklist gates, artifact metadata and digests, and `assignment_order_prepared` event required by the specification. The server instant converts to `2026-08-27` through an explicit `Europe/Moscow` timezone rather than the PHP default timezone.
- **Immutable history within the reviewed adapter:** the command builds new arrays from source snapshots and replaces the process only after all derived facts have been assembled. Subsequent reads return the stored process arrays; later fixture changes to the separate order, installer, engineer, clock, or renderer collections cannot rewrite the prepared version. The implementation exposes no update operation for the prepared version. Existing-version and changing-order behavior is deliberately outside this first-success precondition and must receive its own executable rejection/change slice before production use.
- **Atomicity within the reviewed adapter:** all catalog reads and rendering occur before `replaceProcess(...)`; therefore a thrown renderer/read error leaves the process untouched. Version, assignments, task closure, and event are committed through one in-memory `replaceProcess(...)` call, so the adapter cannot expose an intermediate subset of those facts. Concrete database transactions, artifact-blob persistence, storage failure injection, and concurrency controls are not implemented or claimed by this verdict.
- **Authorization and invariant ordering:** `assignment_order.prepare` remains the first checked invariant. A denied actor returns immediately after the separately protected security event and cannot reach normalization, catalogs, renderer, or process reads. For an authorized actor, required composition is normalized and rejected before any success-path catalog access. All eight independently reviewed `ORDER-PREPARE-001` tests remain green. Other successful-path preconditions are accepted facts of example A; section 8 explicitly defers their public rejection contracts, so this implementation correctly avoids inventing rejection codes or messages for them.
- **Audit and append-only behavior:** the successful event is appended to the copied current event list and saved with the same atomic process replacement. It records the actor, exact server instant, version, Moscow date, normalized composition, engineer, organization type, and both artifact digests. Existing events are retained by `[]` append. The successful path neither writes the closed security journal nor weakens its read authorization.
- **Security and PII:** persisted names, employment facts, source timestamps, and engineer identity are required snapshots of an authorized successful command. They do not enter rejected-command audit paths. The success response itself contains only process metadata; the existing forbidden response continues to reveal neither order state nor participant validation. Broader authorization of `getOrderProcess(...)`, retention, encryption, and production access controls remain outside this slice and must be resolved before exposing a transport endpoint.
- **Integration boundaries:** legacy order data, personnel data, user data, clock, renderer, and persistence remain behind environment capabilities. Production code does not read `../fmonitor`, call networks, select between raw legacy date columns, depend on a particular document library, or write a legacy projection. The renderer receives snapshots and derived document facts, while the module derives SHA-256 and byte size from the returned bytes.
- **Maintainability:** the implementation is a compact orchestration of the approved public seam and introduces no speculative HTTP, UI, 1C DO, PDF/DOCX, or database design. The broad `object` dependency and array-shaped contracts remain temporary technical debt inherited from the prototype boundary; they should be deepened when production adapters are specified, not inside this minimal successful example.
- **Scope control:** the code implements only a first version with `individual` organization and overwrites the process collections under example A's explicit preconditions. Multiple installers, an existing version, missing/invalid snapshots, PТО, renderer/storage failure results, and concurrent commands are not safe general-purpose accepted paths; the specification explicitly reserves them for later vertical slices. No caller should treat this approval as authorizing those paths.
- **Test sensitivity:** the strict public projection assertion catches plausible regressions in date conversion, snapshot capture, version/status/type, preliminary assignments, task and work gates, artifact metadata/digests, and audit content. The test also catches premature work opening and a mistakenly created registration task. As recorded at Gate 3, it does not prove source-mutation stability, rollback under failures, or deferred rejections/concurrency; those claims require new red tests and independent review.

## Required changes

None for the independently reviewed `ORDER-PREPARE-002` example A slice.

Gate 5 is approved. Before this seam is connected to production callers, deferred preconditions—especially existing-version/concurrency protection, personnel and engineer eligibility, required order facts/PТО, renderer and persistence failures, and production transaction/artifact storage—must proceed through their own approved SSD/TDD slices.
