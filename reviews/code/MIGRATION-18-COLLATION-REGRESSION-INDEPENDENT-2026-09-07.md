# Independent code review — migration 18 collation regression

Verdict: **APPROVED** for the exact bounded production change.

Reviewer: `/root/bootstrap_review` (independent from the
`/root/migration18_collation` author). Review date: 2026-09-08 Europe/Moscow.
Source HEAD at review: `8c0cc63f0454b0a620dafa399d5d2462d8447feb`.

Reviewed file:

- `app/InstallationProcess/MariaDbAssignmentOrderSelectionSchemaCatalog.php` — SHA256 `666516cc31f3e02176f75ef0f63b8d53d0e806b4d2b420bd804babbd9b729b86`.

The previous catalog normalized every utf8mb4 column whose actual collation equaled
the database default to `@collation`. With a `utf8mb4_bin` database default this
also normalized `full_snapshot_json`, although migration 18 requires that column's
collation as the literal `utf8mb4_bin`. The resulting actual manifest could never
equal the expected manifest.

The change indexes the already supplied expected column definitions and performs
placeholder normalization only when that exact expected column declares
`@collation`. Literal collations, including `utf8mb4_bin`, remain literal. Missing,
extra, reordered, or otherwise malformed columns still fail through the unchanged
exact manifest comparison; indexes, foreign keys, checks, trigger absence and table
properties remain unchanged. No migration writer or runtime domain behavior moves.

No blocking findings.
