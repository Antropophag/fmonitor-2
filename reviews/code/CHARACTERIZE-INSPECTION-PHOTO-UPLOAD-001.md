# Code review: CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001

- Gate: 5 — independent code review
- Reviewer: separately tasked agent `/root/photo_upload_characterization_code_review_v2`
- Independence: this reviewer did not author the specification, approved test, verifier, or runner wiring
- Reviewed ancestry: HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`; dirty-tree bytes pinned below
- Specification: `specs/CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001.md`, version `0.1`
- Approved test review: `reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001.md`, verdict `APPROVED`
- Public seam: `php rapid-pilot/verify-checklist-photo-upload.php`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

No blocking findings.

The implementation conforms to the corrected 12-lowercase-hex namespace
contract. Token and artifact-root validation occur before the database connection,
namespace query, directory creation, or DDL. The artifact root must be an exact
canonical absolute directory, must not itself be a symlink, and is rejected when
its physical canonical path is `/tmp` or below `/tmp`; the approved meta-test also
proves rejection of lexical `..`, a repository-local symlink, and malformed tokens
without SQL or filesystem mutation.

Namespace ownership is bounded and safe for concurrent harness runs. The SQL query
escapes every LIKE metacharacter in the exact `photo_verify_<token>_` prefix. The
atomic creation of the exact `photo-verify-<token>` directory claims an otherwise
empty token namespace before DDL, while a pre-existing SQL table, file, directory,
or symlink causes setup failure without cleanup of that foreign state. Cleanup is
enabled only after this claim and names only the ten fixed fixture tables plus the
exact private child. It therefore removes partial DDL after a later failure without
discovering or deleting another valid token's namespace. Root and child symlinks
are unlinked rather than followed; the recursive iterator is not configured with
`FOLLOW_SYMLINKS`, so cleanup remains inside the claimed tree.

All MariaDB identifiers fit the platform limit. The longest generated identifier,
`photo_verify_<12 hex>_fm2_checklist_template_associations`, is 61 bytes, below
MariaDB's 64-byte maximum. The fixture creates its verification-only schema
directly and never calls `ChecklistSync::ensureSchema` or another runtime DDL path.

The nominal oracle exercises only public `ChecklistSync::accept(...)` and
`ChecklistSync::projection(...)`. It asserts the complete accepted result and
projected photo metadata, content-addressed blob hash, exact replay result,
unchanged replay projection/blob count, retryable storage infrastructure failure,
and absence of an accepted fact after that failure. Its expected PNG size, MIME,
name, SHA-256, ordered milestones, and transcript hash are literal approved values,
not values copied from production output. The meta-test additionally proves two
byte-identical sequential runs and two independent concurrent runs, an unchanged
ambient decoy, preservation of an explicit foreign valid-token SQL/storage
namespace, exact collision behavior, clean owned namespaces, and setup-failure
classification.

The characterization runner contains exactly the meta-test entry. The verifier
adds no HTTP route, authorization policy, target application seam, or product
behavior; GRILL-003 identity/capability/current-assignment decisions remain outside
the slice. Existing `ChecklistSync` product semantics were inspected but are not
modified by this characterization implementation.

## Verification evidence

```text
php tests/Verification/characterize_inspection_photo_upload_001_test.php  # twice sequentially
PASS — both exits 0; exact deterministic/isolation success line; stderr empty

php tests/Verification/characterize_inspection_photo_upload_001_test.php  # two concurrent processes
PASS — both exits 0; exact deterministic/isolation success line; both stderr empty

make characterization-test
PASS — photo-upload meta-test and every subsequent characterization entry passed

make lint
PASS

make architecture-check
PASS — ARCHITECTURE CHECK PASSED (6 rules)

git diff --check
PASS
```

The focused meta-test includes and passed the direct nominal verifier, missing and
malformed token probes, SQL/storage collision probes, `/tmp`, lexical, and symlink
path probes, repeated-run cleanup checks, foreign-namespace preservation, and an
unavailable-database setup classification probe.

## Reviewed hashes

```text
dad4a0e6cb48095b6c7b86d2d740b705379dcafb9d061838d8aa036c85de4311  specs/CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001.md
fcf6f7f3543e8920522abd282344c7c2dc7a583432cd527cdfc61ca0edebd49a  tests/Verification/characterize_inspection_photo_upload_001_test.php
0630e760d92498cf4ee8610174fb090aaac26475e1a153f03148057c06bad44d  reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001.md
35d49798e61324f3d01827ef808c92fb19f37958be147e2efb9b474ef62a0c42  rapid-pilot/verify-checklist-photo-upload.php
8735fd07cb22dc12d74fb6efd6be0d2da02545f4399201270731bf97fc5e88e2  tools/verification/run.sh
```

Gate 5 is approved for the reviewed bytes.
