# Gate 5 code review: production safe-log parent-component symlink v60

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/safe_log_parent_gate5`
- Exact reviewed implementation: `65988fb6520039def9d00f28a531651e796ef6be`
- Approved RED: `33e94ae4ea28f46098fb50c09d925bce1182ef3a`
- Independent Gate 3 approval/base: `2be20b818cadc271cd5cdf861307faca3731ad10`
- Source Gate 5 findings: `3be68a81a220cf24c001f4f2bdaeda06e305625a`
- Verdict for correction `G5-SAFELOG-1` only: **APPROVED**

## Independence and scope

I did not author the specification, planning artifacts, executable test, RED
evidence, production implementation, Gate 3 review, or Gate 4 evidence. This
append-only review record is my only repository change.

I reviewed only the parent-component canonical-path correction identified as
`G5-SAFELOG-1`. This approval is not a Gate 5 approval for the complete v55
production safe-log amendment or original-upload command. In particular,
`G5-SAFELOG-2` (security-attribute validation on the opened descriptor across
the earlier validation-to-snapshot race) remains explicitly out of scope and
unapproved; its recorded Gate 1 observability gap and its own complete delivery
sequence remain required.

## Exact reviewed artifact identities

```text
4cae80e141ad4caf758e792d0ae5a8383c32f6b9d6a779d6eace6122ec71b255  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
09fbfb8d9a5405989ad40150d011b557d8fea1ba030530fbf68ce06c3a710abb  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
482fb11555e9f10968a2485c2f22e863543c67fa78c41cd8148ce3f1b0e4ce09  openspec/changes/replace-pilot-registration-with-original-upload/design.md
42a03479da33ecc99f2b6e6f76600480a723e7a8c8e25296d33285584d8d3c88  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
dd4c5f05ddfadfcff57a8f4303165a806a43a4b339227407ceac6a37b8d7d2b2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
513d315779988ef87f93b175cddd652188d33a5c2665f2ac4af667e62d526a53  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
067acfd0bb83f05cb73a7c04e22b8001a91898c8c500737a1dbd9e142072d43a  docs/operations/assignment-order-original-production-safe-log-parent-symlink-red-2026-09-05.md
fd2207f0b9f8efb8e4c18735ecdef6aa683a15eab56982e2e272c273d31cb536  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-production-safe-log-parent-symlink-v60.md
4e26ef38bcb9672464a52b59d71da065f39a9af74386ea89a7b4ffe40e632abb  docs/operations/assignment-order-original-production-safe-log-parent-symlink-gate4-green-2026-09-05.md
4c893c34377546ded04fc094bf5cbfd8dd5647655416ec25a8e6e28c65ef114d  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
7f90d23d59ba193ed3fa5f917625e0da0d58176e2ae76c3e6edca73b4efc926f  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php
b2c1ac179fc458cb97805e4b266d89a84439fd4dddf7279b3e6b47e91d4d3ec9  production diff from Gate 3 base through reviewed implementation
```

## Review findings

The implementation makes the minimum production change authorized by the
approved RED: `validate()` now resolves the configured path and requires the
configured lexical identity to equal that resolution. Consequently a final
regular file reached through any task-owned symlinked parent is rejected rather
than silently canonicalized before logger construction. The public production
factory catches that failure and retains the fixed redacted construction
exception before database or private-storage access.

The Darwin exception is narrow and necessary for the existing system-root
identity: only a lexical path beginning `/var/` may compare equal to
`/private` plus that exact lexical path. A symlink in any component below
`/var` changes the remainder of `realpath()` and therefore still fails this
comparison. Other aliases, relative paths, lexical dot components, duplicate
separators, missing entries and final-entry symlinks remain rejected by the
combined checks. Linux and other platforms receive no normalization exception.

The approved test is sensitive to the corrected omission. Its random owned
fixture uses a regular current-EUID exact-`0600` child under a symlinked parent,
calls the public production factory, requires the exact redacted exception, and
previously produced the sole intended mismatch. On the reviewed implementation
it passes. Existing valid, missing, relative, lexical non-canonical,
final-symlink, non-regular, wrong-mode, wrong-owner, device, no-repair,
fail-before-resource and real append assertions also pass, so the correction
does not overreject or regress the already approved matrix.

The diff from the independent Gate 3 base changes one production expression
and adds Gate 4 evidence only. It does not modify executable tests,
specifications, OpenSpec artifacts, configuration, `rapid-pilot/`, storage
boundaries, or runtime DDL. It also does not purport to repair descriptor
integrity.

## Independent verification

All commands ran on exact implementation
`65988fb6520039def9d00f28a531651e796ef6be` and exited `0`:

```text
php -l app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
No syntax errors detected in app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php

php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_PRODUCTION_BOUNDARY_OK
php tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_MAINTENANCE_OK
php tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_LEASE_RACE_OK
php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
php tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_PDF_INCREMENTAL_RED_OK
php tests/InstallationProcess/assignment_order_original_upload_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK
php tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK
php tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

git diff --check
(no output)
```

## Decision

Gate 5 is **APPROVED** for correction `G5-SAFELOG-1` at exact implementation
`65988fb6520039def9d00f28a531651e796ef6be` only. The parent-component
canonical-path finding is closed.

This decision does not approve `G5-SAFELOG-2`, the complete production safe-log
slice, or the combined command. Descriptor-integrity remains an open blocker
until it independently completes approved executable spec, RED, Gate 3,
minimal GREEN and fresh Gate 5.
