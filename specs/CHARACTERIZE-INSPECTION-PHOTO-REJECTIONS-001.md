# CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001 v0.1

Status: approved by the TEST-USER-READY pilot-behavior-inventory mission. This is an executable characterization contract for stable, already product-accepted validation boundaries observable in the rapid pilot. It does not approve target upload authorization, assignment or queued-upload policy, revoke semantics, the pilot 10-photo cap, exact rejection copy, or any other pilot-only behavior.

## Actor and intent

A discovery/test agent needs a deterministic oracle proving that malformed photo evidence is rejected before it can become a server-accepted inspection fact.

## Public oracle seam

- Stable harness entry point: `php rapid-pilot/verify-checklist-photo-rejections.php`.
- The behavioral oracle calls public `ChecklistSync::accept(...)` and `ChecklistSync::projection(...)` only. It SHALL NOT call `storePhoto` or inspect private methods.
- The synthetic `HttpUser` is already admitted. HTTP identity, CSRF, exact capability, current assignment and reassignment/offline policy are outside this characterization and remain blocked by GRILL-003.
- Each characterized result is observable as `status=rejected`; exact Russian `message` text is pilot-only and SHALL NOT be asserted.

## Fixture and isolation contract

1. The caller SHALL supply `FMONITOR_PHOTO_REJECTION_VERIFY_RUN_TOKEN` as exactly 12 lowercase hexadecimal characters. The verifier exclusively owns SQL prefix `photo_reject_<token>_` and storage child `photo-reject-<token>` beneath the exact `FMONITOR_PHOTO_REJECTION_VERIFY_ARTIFACT_ROOT`. It SHALL reject an occupied owned namespace before mutation and SHALL never discover, mutate, or clean another valid token's namespace. No fallback or sibling location is permitted, and `/tmp` is forbidden.
2. The verifier SHALL create the exact verification-only schema and fixture rows directly. It SHALL NOT invoke `ChecklistSync::ensureSchema` or any production runtime DDL path.
3. Every scenario uses its own unique `working` case, revision `0`, immutable template association effective before the operation, section `3`, and fixed actor, UUIDs, device time and server-receipt time. This prevents one rejection or operation identity from affecting another.
4. The common content fixture is the literal valid 1x1 PNG from `CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001`:
   - base64: `iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=`
   - decoded byte size: `68`
   - MIME detected by `finfo(FILEINFO_MIME_TYPE)` and `getimagesizefromstring`: `image/png`
   - SHA-256: `431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460`
   - baseline original filename: `section-3-evidence.png`
5. After every rejection, the verifier SHALL observe through `projection(...)` that revision remains `0` and active photos remain empty. It may query only its own verification tables and owned storage directory to prove that no checklist operation, photo row or blob was created.
6. A meta-test SHALL run the verifier twice with distinct unoccupied tokens, require byte-identical normalized stdout and empty stderr, prove both owned SQL/storage namespaces are removed, and preserve an ambient decoy fingerprint outside those namespaces.
7. Database or fixture setup failure is `SETUP_FAILURE` with exit `2`. Assertion drift, unexpected mutation or cleanup/isolation failure is `REGRESSION_FAILURE` with exit `1`.

## Characterized rejection examples

All unspecified upload-envelope fields equal the valid accepted-upload fixture. Expected values below are fixed by this specification rather than derived from production validation code.

### Detected content does not match declared MIME

- **GIVEN** the common PNG bytes, declared `mime=image/jpeg`, declared `size=68`, the fixed correct SHA-256 and baseline filename
- **WHEN** `ChecklistSync::accept(...)` receives `photo_uploaded`
- **THEN** it returns `rejected`
- **AND** no revision, accepted operation, photo fact or blob is created

Stable milestone: `PHOTO_REJECTION mime-content-mismatch rejected revision=0 active=0 blobs=0`.

This characterizes the product-accepted JPEG/PNG/WebP content-validation boundary, not trust in a filename extension or client-supplied MIME.

### Declared size does not match the bytes

- **GIVEN** the common 68-byte PNG, declared `mime=image/png`, declared `size=67`, the fixed correct SHA-256 and baseline filename
- **WHEN** the upload is accepted at the public seam
- **THEN** it returns `rejected`
- **AND** no revision, accepted operation, photo fact or blob is created

Stable milestone: `PHOTO_REJECTION size-mismatch rejected revision=0 active=0 blobs=0`.

This characterizes exact metadata/content agreement. The separately product-accepted upper boundary of 5 MiB is not duplicated in this narrow transcript; the pilot minimum of one byte is not promoted to a product minimum.

### Declared SHA-256 does not match the bytes

- **GIVEN** the common PNG bytes, correct MIME and size, declared SHA-256 `0000000000000000000000000000000000000000000000000000000000000000`, and baseline filename
- **WHEN** the upload is accepted at the public seam
- **THEN** it returns `rejected`
- **AND** no revision, accepted operation, photo fact or blob is created

Stable milestone: `PHOTO_REJECTION hash-mismatch rejected revision=0 active=0 blobs=0`.

This characterizes integrity agreement only. Pilot storage naming, cross-operation content deduplication and replay payload policy are not approved here.

### Original name contains a control character

- **GIVEN** the common PNG bytes with correct MIME, size and hash, and the exact original name `section-3\nevidence.png` containing U+000A
- **WHEN** the upload is accepted at the public seam
- **THEN** it returns `rejected`
- **AND** no revision, accepted operation, photo fact or blob is created

Stable milestone: `PHOTO_REJECTION invalid-name rejected revision=0 active=0 blobs=0`.

This characterizes the stable safety boundary that an evidence filename is non-empty, at most 255 Unicode characters and contains no C0/DEL control character. It does not prescribe normalization, extension policy or user-visible rejection wording.

## Transcript contract

The verifier SHALL print the four stable milestones above in specification order followed by exactly one terminal line:

`CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001 transcript_sha256=d81a8b99ece0cfff99f32e0f5f535369349c6cce48fc6898ba7e7f193dc055b9`

The transcript SHA-256 is independently the hash of the four milestone lines, each terminated by LF, in the order shown. No volatile identifier, path, table name, row id or translated pilot message may appear in normalized stdout.

## Classification boundaries

- `PRODUCT_ACCEPTED` and characterized here: evidence bytes must decode as the declared supported image MIME; declared byte size and SHA-256 must match the bytes; original name must satisfy the bounded safe-metadata contract; rejection creates no accepted inspection fact or revision.
- `PRODUCT_ACCEPTED` but inherited rather than re-characterized here: JPEG/PNG/WebP, maximum 5 MiB, evidence attached to a checklist snapshot section, actor/device/server time on acceptance, and stable client operation identity.
- `PRODUCT_ACCEPTED_BUT_NOT_IMPLEMENTED`: image dimensions and optional caption.
- `PILOT_ONLY`, explicitly not promoted: exact Russian rejection messages, section ids 1..8, stale-base acceptance, same-content/new-operation duplicate behavior, 10-photo cap, global hash filename, payload-unaware replay, current revoke behavior and orphan-blob behavior after a later database failure.
- `UNRESOLVED`: exact capability plus current assignment, queued upload after reassignment, and revoke reason/confirmation/history (GRILL-003).

## RED evidence required for Gate 2

Gate 2 is intentionally not performed by this specification change. The future test author SHALL capture the command and focused failure from a clean invocation of the missing harness, expected initially as:

`php rapid-pilot/verify-checklist-photo-rejections.php`

The qualifying RED must fail because the focused verifier/expectation is absent or because one characterized rejection boundary is not yet enforced. Database unavailability, invalid environment, missing fixture schema, permissions, or unrelated suite failure is setup failure and is not RED evidence.

## Done definition

This slice is done only after all mandatory gates in `docs/development-process.md` complete: reviewed RED for this exact specification; independent test approval; minimal verifier/meta-test GREEN; both deterministic runs and leak/decoy checks pass; existing photo-upload and checklist characterizations remain green; `make architecture-check`, relevant regression, `make verify` and diff checks are green; and a separately tasked independent code reviewer records `APPROVED`.

Done does not implement or approve a target application seam, authorization/assignment policy, photo revoke, 10-photo cap, or production behavior. This v0.1 file completes the characterization Gate 1 contract only, not Gate 2 or the characterization implementation.
