# CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001 v0.1

Status: approved by the TEST-USER-READY pilot-behavior-inventory mission. This is a characterization harness contract, not approval of target upload authorization, revoke semantics, or current pilot defects.

## Actor and intent

A discovery/test agent needs a deterministic oracle for the smallest existing rapid-pilot photo-upload behavior before the capability moves behind `InspectionRecording::uploadPhoto`.

## Public oracle seam

- Stable harness entry point: `php rapid-pilot/verify-checklist-photo-upload.php`.
- Behavioral oracle calls public `ChecklistSync::accept(...)` and `ChecklistSync::projection(...)` only.
- The synthetic `HttpUser` is already admitted; HTTP identity, CSRF, capability and current-assignment policy are explicitly outside this slice and remain GRILL-003.

## Fixture and isolation contract

1. The caller SHALL supply `FMONITOR_PHOTO_VERIFY_RUN_TOKEN` as exactly 12 lowercase hexadecimal characters. This keeps the longest prefixed table identifier within MariaDB's 64-byte limit while retaining a 48-bit random harness namespace. The verifier exclusively owns SQL prefix `photo_verify_<token>_` and storage child `photo-verify-<token>` beneath the exact `FMONITOR_PHOTO_VERIFY_ARTIFACT_ROOT`; it SHALL reject an occupied owned namespace before mutation and SHALL never discover, mutate, or clean another valid token's namespace. No fallback or sibling location is permitted, and `/tmp` is forbidden.
2. It SHALL create exact verification-only schema/rows directly and SHALL NOT invoke `ChecklistSync::ensureSchema` or any production runtime DDL path.
3. Fixture facts are fixed: one unique `working` case, immutable template association effective before the operation, section 3, fixed actor/UUID/timestamps, and a literal valid PNG whose byte length and SHA-256 are independently fixed in the verifier.
4. A meta-test SHALL run the verifier twice, require byte-identical normalized stdout and empty stderr, prove no private prefixed tables/storage directories remain, and preserve an ambient decoy fingerprint.
5. Database unavailability is `SETUP_FAILURE` with exit 2; assertion drift or fixture/storage leak is `REGRESSION_FAILURE` with exit 1.

## Approved worked fixture

The accepted-upload input is this pre-implementation literal 1×1 PNG; the verifier consumes these bytes but is not the source of their expected metadata:

- base64: `iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=`
- decoded byte size: `68`
- MIME detected by `finfo(FILEINFO_MIME_TYPE)`: `image/png`
- original filename: `section-3-evidence.png`
- SHA-256: `431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460`

The terminal transcript SHA-256 is independently the hash of the three exact milestone lines in this specification: `7603606ad948a2bf464ccb02ee5e797c4daeb6580ca9b0fd85e07fb102d5067d`.

## Characterized pilot behavior

### Accepted upload is projected

- **GIVEN** base revision `0`, a valid `photo_uploaded` envelope and matching PNG bytes
- **WHEN** the oracle accepts the operation
- **THEN** it returns `accepted` revision `1`
- **AND** projection revision is `1` with exactly one active section-3 photo containing the fixed operation id, hash, detected MIME, byte size, original name, actor, device time and server-receipt time
- **AND** the content-addressed blob exists with the independently fixed SHA-256

Stable milestone: `PHOTO_UPLOAD accepted revision=1 active=1 blob_sha256=<fixed hash>`.

### Exact client-operation replay is idempotent

- **GIVEN** the accepted upload above
- **WHEN** the exact same operation and bytes are replayed
- **THEN** pilot returns `duplicate` revision `1`
- **AND** projection and blob count remain unchanged

Stable milestone: `PHOTO_UPLOAD replay duplicate revision=1 active=1`.

### Storage creation failure is retryable and creates no accepted fact

- **GIVEN** an otherwise valid clean-case upload whose configured storage root is an existing regular file
- **WHEN** the pilot attempts to create its `checklist` directory
- **THEN** `ChecklistSync::accept` throws `PilotHttpInfrastructureUnavailable`
- **AND** projection remains revision `0` with no photos and no blob

Stable milestone: `PHOTO_UPLOAD storage-unavailable retryable revision=0 active=0`.

The harness SHALL finish with exactly one stable `CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001 transcript_sha256=<hash>` line after the milestones.

## Classification boundaries

- `PRODUCT_ACCEPTED`, but only characterized here where implemented: evidence belongs to a checklist snapshot section; case is open/working; server-accepted fact records actor/device/server time and technical file metadata; MIME is JPEG/PNG/WebP and size is at most 5 MiB; client operation identity is stable.
- `PRODUCT_ACCEPTED_BUT_NOT_IMPLEMENTED`: image dimensions and optional caption. They are excluded from this current-oracle transcript and belong in the later target-slice RED.
- `PILOT_ONLY`, not promoted here: hard-coded sections 1..8, exact Russian rejection messages, stale-base acceptance, same-content/new-operation duplicate, 10-photo cap, global hash filename, payload-unaware replay, current revoke behavior, and orphan blob after a later DB failure.
- `UNRESOLVED`: exact capability plus current assignment, queued upload after reassignment, and revoke reason/confirmation/history (GRILL-003).

## Done definition

Reviewed RED proves the focused verifier is missing; minimal verifier/meta-test implementation passes twice; existing checklist characterization, architecture and diff checks remain green; fresh independent code review is approved. No target application seam or product authorization behavior is implemented.
