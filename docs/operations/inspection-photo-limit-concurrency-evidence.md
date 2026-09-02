# Inspection photo limit and concurrency evidence

Date: 2026-09-01  
Scope: discovery only; current rapid-pilot behavior, not an approved target contract.

## Sources inspected

- `PRODUCT.md`, `CONTEXT.md`, `docs/fmonitor-2-pilot-spec.md`, `docs/fmonitor-2-pilot-data-model.md`.
- `app/PilotHttp/ChecklistSync.php`, `app/PilotHttp/checklist.js`, and the checklist HTTP dispatch in `app/PilotHttp/PilotE2ECoordinator.php`.
- `specs/CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001.md` and `specs/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md` with their tests and public verifiers.
- PB-05 and GRILL-003 in `docs/operations/pilot-behavior-inventory.md` and `docs/operations/migration-backlog-and-grill.md`.

## Exact current behavior

`ChecklistSync::accept(...)` is the public oracle seam. Before photo-specific work it validates the operation envelope, performs an unlocked `client_operation_id` duplicate lookup, starts a transaction, locks the installation-case row with `SELECT ... FOR UPDATE`, validates the working case and template association, and locks/reads the checklist revision. Therefore all accepted operations for one installation case, including uploads to different sections, are serialized by the case-row lock.

For `photo_uploaded`, `storePhoto(...)` applies these checks in order:

1. validate bytes, JPEG/PNG/WebP content/MIME agreement, 1..5 MiB size agreement, lowercase SHA-256 agreement, and bounded/control-free original name;
2. return `duplicate` at the current revision when an active photo with the same `(case, section, sha256)` exists;
3. count active rows for `(case, section)` and reject when the count is already at least 10;
4. create/reuse the global content-addressed `<sha256>.bin` blob;
5. insert the photo row, then append the `photo_uploaded` operation, increment revision, and commit.

The browser independently queues only `files.slice(0, max(0, 10 - current.length))`. Its `current.length` includes locally projected queued/sending uploads as well as server photos. The server remains authoritative and counts only non-revoked persisted photo rows. The HTTP layer enforces the 5 MiB request boundary but adds no count rule.

With nine active photos and two different valid uploads racing for the same case/section, the case lock makes the outcome deterministic by lock acquisition order: the first transaction accepts one photo and advances the revision; the second observes ten active rows and returns `rejected` without adding an operation, photo row, blob, or revision. Both operations may carry the same stale base revision because only `baseRevision > currentRevision` conflicts; stale lower bases are accepted. Which upload wins is deliberately unspecified.

At ten active photos, a same-content upload is classified `duplicate` before the count check, while a new hash is rejected. The exact Russian rejection message is presentation behavior, not a stable contract.

## Idempotency and failure boundaries

- Exact replay after commit: the initial `client_operation_id` lookup returns `duplicate` with the original accepted revision and causes no mutation. This is already characterized by `CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001`.
- Same content with a new operation id: returns `duplicate` by active content identity, creates no operation record, and does not advance revision. This is current pilot behavior only.
- Concurrent exact same operation and bytes: the loser can pass the pre-transaction operation lookup, but after acquiring the case lock it sees the active same-content photo and returns `duplicate`; no second fact is created.
- Concurrent same operation id with different bytes: the loser does not re-check operation identity after acquiring the case lock. It may write a second content-addressed blob and photo row before the unique operation insert fails. Database rollback removes its rows; the catch then reports `duplicate` from the winner, but the newly written blob can remain orphaned. The response is payload-unaware.
- Storage succeeds but a later SQL statement fails: database state rolls back, while a newly created blob is not transactionally removed. This known orphan-blob boundary is not suitable for promotion.
- The schema has an unconditional unique key on `(installation_case_id, section_id, sha256)`, despite queries treating only `revoked_at IS NULL` as active. Re-uploading identical content after revoke can therefore raise a SQL exception rather than create a new active fact. This belongs to the separate revoke design.

## Classification

- **PRODUCT_ACCEPTED:** photo evidence belongs to an immutable checklist snapshot section; an accepted fact records actor/device/server time and validated file metadata; client operation identity is stable; acceptance must not exceed a product-approved evidence policy under concurrency.
- **PILOT_ONLY:** the numeric cap of 10, client-side truncation, stale-base acceptance, same-content/new-operation deduplication, global hash filename, payload-unaware replay, exact rejection copy, and serialization of every checklist operation through one case row.
- **UNKNOWN / needs product decision:** the target maximum active-photo count and whether it is per section, per inspection visit, or another scope; expected user outcome when queued uploads exceed that limit; whether a rejected overflow remains locally retryable/removable; target payload-conflict semantics for reuse of a client operation id.
- **GRILL-003 only:** exact upload capability/current-assignment policy and queued upload after reassignment. Those questions are orthogonal to characterizing the pilot limit/concurrency oracle.

## Persistence and verifier inventory

- Mutated: `fm2_checklist_photos`, `fm2_checklist_operations`, `fm2_checklist_revisions`; filesystem `<storageRoot>/checklist/<sha256>.bin`.
- Locked/read: `fm2_installation_cases`, `fm2_checklist_template_associations`, `fm2_checklist_template_snapshots`; duplicate and limit checks read the three mutated tables.
- Existing focused verifiers: `rapid-pilot/verify-checklist-photo-upload.php` covers one accept, exact replay, and storage-unavailable rollback; `rapid-pilot/verify-checklist-photo-rejections.php` covers four validation rejections. Neither covers the tenth/eleventh boundary or two-connection concurrency.

## Recommended next slice

Create `CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001` as a narrow oracle characterization, still explicitly `PILOT_ONLY`:

- public seam only (`ChecklistSync::accept` and `projection`);
- seed nine distinct active photos, race two distinct valid uploads through two MariaDB connections/processes, and assert exactly one acceptance, one overflow rejection, ten active photos, one revision increment, and no loser blob;
- separately prove same-content-at-cap returns duplicate without mutation;
- do not assert which contender wins, exact message text, authorization, target numeric limit, or target stale-base/replay policy.

This characterization can be approved under the pilot-behavior-inventory mission without GRILL-003 because it records an existing oracle and expressly does not authorize target behavior. It must not become the executable product spec for `InspectionRecording::uploadPhoto`. The target upload slice remains blocked on GRILL-003 for authorization and on the product questions above for the limit/overflow policy.
