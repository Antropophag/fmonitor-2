# CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001 v0.1

Status: approved by the existing TEST-USER-READY pilot-behavior-inventory mission for Gate 1. This is a `PILOT_ONLY` executable characterization contract for an observed rapid-pilot oracle. It is not product-owner approval of a target photo limit, overflow UX, deduplication policy, authorization policy, or `InspectionRecording::uploadPhoto` behavior.

## Actor and intent

A discovery/test agent needs a deterministic record of how the rapid pilot behaves when two valid photo uploads contend for the final currently available place in one checklist section, and how the same-content case is classified after that place has been filled.

The purpose is inventory and migration safety: a later target slice may deliberately replace this behavior after product decisions. `UNKNOWN` target semantics are not promoted by this specification.

## Public oracle seam

- Stable future harness entry point: `php rapid-pilot/verify-checklist-photo-limit-concurrency.php`.
- Each contender SHALL use its own process and MariaDB connection and SHALL call public `ChecklistSync::accept(...)`. Observations of checklist state SHALL use public `ChecklistSync::projection(...)`.
- The oracle SHALL NOT call `storePhoto`, another private method, an HTTP dispatcher, or browser code. Direct SQL is allowed only to create and audit the isolated verification fixture; it is not the behavioral seam.
- The synthetic `HttpUser` is already admitted. Identity, CSRF, exact upload capability, current assignment, queued upload after reassignment and all authorization policy remain outside this characterization and blocked by GRILL-003.

## Fixture and isolation contract

1. The caller SHALL supply `FMONITOR_PHOTO_LIMIT_VERIFY_RUN_TOKEN` as exactly 12 lowercase hexadecimal characters. The verifier exclusively owns SQL prefix `photo_limit_<token>_` and storage child `photo-limit-<token>` beneath the exact `FMONITOR_PHOTO_LIMIT_VERIFY_ARTIFACT_ROOT`. It SHALL reject an occupied owned SQL or storage namespace before mutation. No fallback, sibling location or `/tmp` storage is permitted.
2. The verifier SHALL create the exact verification-only schema and fixture rows directly. It SHALL NOT invoke `ChecklistSync::ensureSchema` or any production runtime DDL path.
3. The fixture contains one unique `working` installation case, one immutable template association effective before all operations, section `3`, revision `9`, and exactly nine distinct active photo rows in that section. The nine seed rows and their fixture blobs are owned setup, not accepted operations under test.
4. Contenders A and B SHALL have different valid supported-image bytes, different lowercase content SHA-256 values, different client operation UUIDs, fixed actor/device/server times and otherwise valid `photo_uploaded` envelopes. Both SHALL submit base revision `9`. Their literal bytes and metadata SHALL be pinned in the Gate 2 test from a source independent of `ChecklistSync`; expected outcomes SHALL not be calculated by copying production validation or limit logic.
5. A parent harness SHALL start two child processes with separate MariaDB connections, hold both behind one start barrier until each reports ready, release them together, collect both terminal results, and impose a bounded timeout. Which connection acquires the case-row lock first is deliberately unspecified. A child crash, timeout, unreadable result, or failure to establish two distinct connections is a harness/setup failure, not an overflow result.
6. After the race, a fresh public projection SHALL be used for observable checklist state. The verifier may additionally query only its owned verification tables and inspect only its owned storage child to prove exact aggregate mutation, absence of loser mutation, and cleanup. It SHALL not inspect or alter any unowned table or storage path.
7. A meta-test SHALL run the verifier twice with distinct unoccupied tokens, require byte-identical normalized stdout and empty stderr, prove both owned SQL/storage namespaces are removed, and preserve an ambient decoy fingerprint outside both namespaces. Cleanup SHALL run after pass, regression, setup failure and child failure, and SHALL be bounded to the exact validated owned prefix and storage child.

## Characterized race: two distinct uploads contend at nine active photos

- **GIVEN** the isolated section has revision `9` and exactly nine distinct active photos
- **AND** contenders A and B are both valid, distinct uploads submitted from separate processes/connections with base revision `9`
- **WHEN** the start barrier releases both calls to `ChecklistSync::accept(...)`
- **THEN** exactly one contender returns `status=accepted` and revision `10`
- **AND** exactly one contender returns `status=rejected`
- **AND** the winner may be A or B; neither process order, content hash, operation id nor filename is asserted as the winner
- **AND** public projection reports revision `10` and exactly ten active photos in section `3`
- **AND** the projected tenth photo is exactly the accepted contender's operation/content metadata and the rejected contender is absent
- **AND** aggregate persistence contains exactly one newly accepted checklist operation and exactly one newly inserted photo row
- **AND** storage contains exactly one newly created contender blob: the accepted contender's bytes under the pilot content-addressed name
- **AND** there is no operation row, photo row, revision increment, blob or other state mutation attributable to the rejected contender

Stable winner-neutral milestone: `PHOTO_LIMIT race accepted=1 rejected=1 revision=10 active=10 operations_added=1 photos_added=1 blobs_added=1 loser_mutations=0`.

This example records the current case-row serialization and overflow outcome. It intentionally does not assert the exact rejection message or which contender wins.

## Characterized same-content case at the filled pilot cap

- **GIVEN** the completed race state at revision `10` with ten active section-3 photos
- **AND** a new client operation id submits the exact accepted contender's bytes and matching metadata to the same case and section
- **WHEN** `ChecklistSync::accept(...)` receives that operation through the public seam
- **THEN** it returns `status=duplicate` and revision `10`
- **AND** public projection remains revision `10` with exactly ten active photos
- **AND** no operation row, photo row, revision increment or blob is added or changed

Stable winner-neutral milestone: `PHOTO_LIMIT same-content-at-cap duplicate revision=10 active=10 operations_added=0 photos_added=0 blobs_added=0`.

This is current pilot classification only. It does not approve same-content/new-operation deduplication for the target product.

## Transcript contract

Normalized stdout SHALL contain the two stable milestones above in specification order, followed by exactly one terminal line:

`CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001`

No winner identity, volatile UUID, content hash, path, table name, row id, timing, lock order or translated pilot message may appear in normalized stdout. Exact approved artifact and transcript hashes are intentionally absent at Gate 1; they SHALL be recorded only after Gate 2 has produced the reviewed RED artifacts.

## Classification boundaries

- `PILOT_ONLY`, characterized here and not promoted: the numeric cap of ten active photos per section; serialization of all same-case checklist operations through the installation-case row; stale lower base revision acceptance; same-content/new-operation duplicate classification; global hash filename; exact overflow ordering and current presentation copy.
- `PRODUCT_ACCEPTED`, inherited context only: accepted evidence belongs to an immutable checklist snapshot section, carries validated supported-image metadata plus actor/device/server time, and must not exceed a product-approved evidence policy under concurrency. This slice does not choose that policy.
- `UNKNOWN` and excluded: the target numeric maximum and its scope (section, visit, or another unit); overflow queue/retry/remove UX; payload-conflict semantics for reuse of a client operation id.
- `GRILL-003` and excluded: exact upload capability, current-assignment requirement, and queued upload after reassignment.
- Also excluded: stale-base target policy; same operation id with a different payload; same-content target deduplication; revoke/re-upload semantics; orphan blobs after later SQL failure; client-side truncation; exact Russian messages; image dimensions and captions.

## Failure classification

- `SETUP_FAILURE` with exit `2`: unavailable MariaDB or storage root; invalid/missing environment; occupied owned namespace detected before mutation; schema/fixture construction failure; inability to establish two distinct connections/processes or reach/release the barrier; child crash/timeout/protocol failure before a behavioral result.
- Qualifying Gate 2 `RED`: the focused verifier/meta-test is absent, or the running oracle violates one exact aggregate acceptance statement above while the isolated environment and two-process harness are healthy.
- `REGRESSION_FAILURE` with exit `1`: unexpected result cardinality/status/revision; wrong aggregate operation/photo/blob count; loser mutation; nondeterministic normalized transcript; isolation/cleanup leak; decoy damage; or drift in an already implemented characterization assertion.

Environment/setup failure SHALL never be reported as RED or as a rapid-pilot behavior regression.

## RED evidence required for Gate 2

Gate 2 is intentionally not performed by this specification change. The future test author SHALL create the smallest focused meta-test for this contract, run it from a clean invocation, and retain the exact command plus relevant intended failure in the independent test-review record. The expected initial focused command is:

`php rapid-pilot/verify-checklist-photo-limit-concurrency.php`

The test and verifier SHALL not weaken the winner-neutral aggregate assertions to make scheduling deterministic. Exact SHA-256 values of the approved specification, test, verifier and normalized transcript SHALL be pinned only after the Gate 2 artifacts exist and before Gate 3 approval.

## Done definition

This slice is done only after every mandatory gate in `docs/development-process.md` completes:

1. this Gate 1 executable characterization spec remains approved under the pilot-behavior-inventory mission;
2. a focused intended RED for this exact contract is demonstrated and recorded;
3. a separately tasked independent test reviewer records `APPROVED` with the pinned artifact hashes;
4. minimal verifier/meta-test GREEN proves two distinct processes/connections, winner-neutral aggregate invariants, same-content-at-cap behavior, deterministic transcript, namespace cleanup and decoy preservation;
5. existing photo-upload, photo-rejection and checklist characterizations remain green;
6. `make architecture-check`, relevant regression, `make verify` and diff checks complete without a new regression;
7. a fresh, separately tasked independent code reviewer records `APPROVED`.

Done does not implement or approve a target application seam, target authorization, target numeric limit or limit scope, overflow UX, stale-base/replay/payload-conflict policy, revoke, or any production behavior. This v0.1 file completes Gate 1 only.
