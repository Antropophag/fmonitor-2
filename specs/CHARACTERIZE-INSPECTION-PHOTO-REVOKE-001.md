# CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001 v0.2

Status: v0.1 was approved as `PILOT_ONLY` evidence of the observed SQL uniqueness failure. That historical milestone remains recorded below but is superseded for active verification by the owner's later `APPROVED_PERMANENT_RETENTION` decision GRILL-007. Version 0.2 changes only identical-content re-upload to the approved target outcome; upload, revoke, replay and already-revoked behavior retain their prior characterization. Updated tests require fresh independent Gate 3 review before implementation.

## Actor and intent

A discovery/test agent needs a deterministic oracle proving how the rapid pilot records a photo upload followed by revoke, handles sequential retry and a second revoke, and fails an identical-content re-upload. The characterization preserves migration evidence without promoting the observed persistence design or defects into target requirements.

## Public oracle seam

- Stable future harness entry point: `php rapid-pilot/verify-checklist-photo-revoke.php`.
- Every upload and revoke command SHALL call public `ChecklistSync::accept(...)`. Observable checklist state SHALL be read through public `ChecklistSync::projection(...)`.
- The oracle SHALL NOT call `storePhoto`, another private method, an HTTP dispatcher, or browser code. Direct SQL and filesystem reads are permitted only to create and audit the isolated verification fixture and to prove the persistence effects required below; they are not the behavioral seam.
- The synthetic `HttpUser` is already admitted. HTTP identity, CSRF, exact revoke capability, current assignment, supervisor override and queued-operation behavior after reassignment remain outside this characterization and blocked by GRILL-003.

## Fixture and isolation contract

1. The caller SHALL supply `FMONITOR_PHOTO_REVOKE_VERIFY_RUN_TOKEN` as exactly 12 lowercase hexadecimal characters. The verifier exclusively owns SQL prefix `photo_revoke_<token>_` and storage child `photo-revoke-<token>` beneath the exact `FMONITOR_PHOTO_REVOKE_VERIFY_ARTIFACT_ROOT`. It SHALL reject an occupied owned SQL or storage namespace before mutation, SHALL never discover, mutate or clean another valid token's namespace, and SHALL use no fallback or sibling location. `/tmp` is forbidden.
2. The verifier SHALL create the exact verification-only schema and fixture rows directly. It SHALL NOT invoke `ChecklistSync::ensureSchema` or any production runtime DDL path.
3. The fixture contains one unique `working` installation case at revision `0`, one immutable template association effective before both commands, section `3`, and fixed actor, canonical client/device UUIDs, operation UUIDs, device times and injected server-receipt times. The upload and revoke use different operation UUIDs. The fresh already-revoked attempt, identical-content re-upload and active-identical duplicate probe each use another different operation UUID.
4. The accepted-upload input is the independently fixed literal 1x1 PNG used by `CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001`:
   - base64: `iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=`
   - decoded byte size: `68`
   - detected MIME: `image/png`
   - original filename: `section-3-evidence.png`
   - SHA-256: `431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460`
5. Before each zero-mutation assertion, the verifier SHALL capture an exact fingerprint of its owned checklist revision, ordered operation rows and payloads, photo row including `revoked_at`, and owned blob count plus SHA-256. It SHALL compare the same fields afterward. Database-generated presentation names, translated exception text, filesystem timestamps and volatile row identifiers SHALL NOT be transcript values.
6. A meta-test SHALL run the verifier twice with distinct unoccupied tokens, require byte-identical normalized stdout and empty stderr, prove both owned SQL/storage namespaces are removed, and preserve an ambient decoy fingerprint outside those namespaces. Cleanup SHALL run after pass, regression, setup failure and thrown SQL failure, and SHALL be bounded to the exact validated owned prefix and storage child.

## Characterized pilot behavior

### Upload followed by revoke is accepted at revision 2

- **GIVEN** the clean revision-`0` fixture and the fixed valid PNG upload at base revision `0`
- **WHEN** `ChecklistSync::accept(...)` accepts `photo_uploaded`
- **THEN** it returns `accepted` revision `1`, and projection exposes exactly one active section-3 photo
- **WHEN** a valid `photo_revoked` envelope at base revision `1` names that projected integer photo id and is accepted through the same public seam
- **THEN** it returns `accepted` revision `2`
- **AND** public projection reports revision `2` with no active photos
- **AND** SQL audit proves exactly one photo row remains, with its original upload metadata unchanged and `revoked_at` equal to the fixed injected revoke receipt time
- **AND** SQL audit proves exactly two ordered operation-history rows remain: the original `photo_uploaded` fact at accepted revision `1`, then one `photo_revoked` fact at accepted revision `2` whose payload contains that photo id
- **AND** the revision fact is `2`
- **AND** exactly one owned content-addressed blob remains and its bytes have the fixed SHA-256

Stable milestone: `PHOTO_REVOKE accepted revision=2 active=0 photo_rows=1 revoked_rows=1 operations=2 blobs=1`.

This records projection omission plus current SQL/blob/history retention as pilot evidence. It does not approve mutable `revoked_at` as target history design or approve a blob-retention period.

### Exact sequential revoke replay is idempotent

- **GIVEN** the committed revision-`2` state above
- **WHEN** the exact same revoke envelope, including the same client operation UUID and payload, is replayed sequentially through `ChecklistSync::accept(...)`
- **THEN** it returns `duplicate` with the original accepted revision `2`
- **AND** projection remains revision `2` with no active photos
- **AND** the complete owned SQL/blob fingerprint is unchanged

Stable milestone: `PHOTO_REVOKE replay duplicate revision=2 active=0 mutations=0`.

This example characterizes sequential exact replay only. Concurrent exact replay is explicitly excluded.

### A new operation against the already-revoked photo is rejected

- **GIVEN** the same committed revision-`2` state
- **AND** a new valid revoke envelope with a fresh client operation UUID names the already-revoked photo
- **WHEN** it is submitted through `ChecklistSync::accept(...)`
- **THEN** it returns `rejected`
- **AND** projection remains revision `2` with no active photos
- **AND** the complete owned SQL/blob fingerprint is unchanged

Stable milestone: `PHOTO_REVOKE already-revoked rejected revision=2 active=0 mutations=0`.

Exact rejection copy is presentation-only and SHALL NOT be asserted.

### Identical-content re-upload is accepted as a new retained fact

- **GIVEN** the same committed revision-`2` state and retained fixed-hash blob
- **AND** a new `photo_uploaded` envelope uses a fresh client operation UUID but the same case, section, fixed PNG bytes, MIME, size, original name and SHA-256
- **WHEN** it is submitted through `ChecklistSync::accept(...)`
- **THEN** the public seam returns `accepted` revision `3` without SQL exception
- **AND** projection reports revision `3` with exactly one active section-3 photo whose photo id and upload operation id differ from the revoked photo
- **AND** SQL audit proves exactly two photo rows: the entire first row remains byte-identical to the post-revoke snapshot, and the second has the new upload identity with the same content metadata/storage name
- **AND** operation history contains exactly three facts ordered by accepted revision: original `photo_uploaded`, `photo_revoked`, new `photo_uploaded`
- **AND** exactly one owned physical blob remains with the same fixed SHA-256

Stable milestone: `PHOTO_REVOKE identical-reupload accepted revision=3 active=1 photo_rows=2 revoked_rows=1 operations=3 blobs=1`.

Historical v0.1 milestone `PHOTO_REVOKE identical-reupload sql-unique-violation revision=2 active=0 mutations=0 blobs=1` remains provenance of the predecessor defect and is not an active success expectation. GRILL-007 authorizes the v0.2 target result and permanent retention.

## Transcript contract

Normalized stdout SHALL contain the four stable milestones above in specification order, each terminated by LF, followed by exactly one terminal line:

`CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001 transcript_sha256=09b498760d9ed265d35372bdd6630e1959bb3073abf2ac2692ef0371d0ccfe0c`

The v0.2 transcript SHA-256 is independently the hash of the four active milestone lines, each terminated by LF, in the order shown. The v0.1 transcript hash `60f1a4c65be2a4cedd05f170b243d34283560f480f37a2965fec7aeadd62b784` remains historical provenance only. No token, UUID, path, table or constraint name, volatile row id, timestamp, exception text or translated pilot message may appear in normalized stdout.

## Classification boundaries

- `PILOT_ONLY`, characterized here and not promoted: accepted revision `2` for this exact upload/revoke sequence; mutable `revoked_at`; the current operation payload shape; sequential duplicate classification; new-operation already-revoked rejection; hard-coded section `3`; stale-base handling; exact persistence schema and exact messages. The v0.1 SQL uniqueness failure remains historical evidence, not an active requirement.
- `PRODUCT_ACCEPTED`: only current photo evidence contributes to readiness; original accepted/revoked evidence and audit history must not be silently erased; identical bytes after revoke create a new evidence identity while one content-addressed blob may be reused; active and revoked evidence/blobs have permanent retention; state-changing inspection evidence belongs behind an explicit application seam. Authorization remains governed by GRILL-007 and existing upload/revoke contracts.
- `UNKNOWN` and excluded: supervisor readiness-correction command details, queued revoke after reassignment and correction/undo behavior beyond GRILL-007.
- Also excluded: HTTP/CSRF presentation changes, concurrent exact revoke replay, concurrent different-operation revoke, UI local-hide behavior, captions/image dimensions, numeric photo limit and exact user-visible messages. Same-case concurrent identical upload and authorization preservation belong to the target slice rather than this deterministic sequential transcript.

## Failure classification

- `SETUP_FAILURE` with exit `2`: unavailable MariaDB or storage root; invalid/missing environment; occupied owned namespace detected before mutation; verification schema/fixture construction failure; inability to create/read the fixed fixture blob; or cleanup infrastructure failure before a behavioral assertion can be evaluated.
- Qualifying Gate 2 `RED`: the focused verifier/meta-test is absent, or the healthy isolated oracle violates one exact acceptance statement in this specification.
- `REGRESSION_FAILURE` with exit `1`: unexpected result/exception classification or revision; any SQL exception in identical re-upload; projection/SQL/blob/history mismatch; failed required immutable-row or zero-mutation fingerprint; nondeterministic transcript; owned namespace leak; or decoy damage.

Environment/setup failure SHALL never be reported as RED or as a rapid-pilot behavior regression. The predecessor MariaDB uniqueness exception is no longer expected; on v0.2 canonical schema it is `REGRESSION_FAILURE`.

## RED evidence required for Gate 2

Gate 2 is intentionally not performed by this specification change. The future test author SHALL create the smallest focused meta-test for this contract and retain the exact clean command plus intended failure in the independent test-review record. The expected initial focused command is:

`php rapid-pilot/verify-checklist-photo-revoke.php`

The qualifying RED must fail because this focused verifier/expectation is absent or because one characterized oracle assertion is unmet while MariaDB, fixture construction and storage isolation are healthy. Exact SHA-256 values of the approved specification and Gate 2 test SHALL be pinned before Gate 3 approval. The implemented verifier hash SHALL be pinned by the independent Gate 5 review; Gate 3 cannot approve a future implementation artifact.

## Done definition

This slice is done only after every mandatory gate in `docs/development-process.md` completes: this Gate 1 contract remains approved under the pilot-behavior-inventory mission; intended focused RED is demonstrated; a fresh separately tasked test reviewer records `APPROVED`; minimal verifier/meta-test GREEN proves all four milestones, deterministic replay, SQL/blob/history fingerprints, exact cleanup and decoy preservation; existing photo upload, rejection, limit/concurrency and checklist characterizations remain green; `make architecture-check`, relevant regression, `make verify` and diff checks introduce no new regression; and a fresh separately tasked code reviewer records `APPROVED`.

Done does not implement supervisor correction, change upload/revoke authorization, weaken reason/confirmation, or approve UI behavior. Version 0.2 advances only after updated tests receive independent Gate 3 approval, canonical schema and target behavior are GREEN, relevant regressions/architecture pass, and independent code review approves exact artifacts.
