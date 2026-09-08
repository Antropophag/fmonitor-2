# PILOT-SESSION-STORAGE-001 — sequential write RED correction v2

- Date: `2026-09-04`
- Gate: `2` correction discovered during Gate 4
- Prior Gate 3: `reviews/tests/PILOT-SESSION-STORAGE-001-sequential-write-identity-v1.md`
- Production changes: none

The v1 linear entropy fixture placed a defect-only 32-byte candidate before the
second normal 16-byte stage token. Correct production therefore received a
wrong-length value and failed before the approved final assertion.

V2 uses a test-local length-keyed recording entropy port. Both paths are fully
constructible: the defect may request the second 32-byte ID, while correct
production requests the second 16-byte stage token. The recorder independently
retains exact request order. All cookie/material/reopen/event/orphan and cleanup
assertions remain unchanged.

Fresh current production reaches the intended aggregate RED without warning or
setup failure: cookie identity is stale, updated token lives under a second
committed ID, reopened cookie payload is old, entropy order differs and
committed events contain the alternate identity. Fresh independent Gate 3 is
required before Gate 4 resumes.
