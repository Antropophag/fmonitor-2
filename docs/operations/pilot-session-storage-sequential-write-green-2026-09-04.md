# PILOT-SESSION-STORAGE-001 — sequential write identity GREEN

- Date: `2026-09-04`
- Gate 3 v2: `reviews/tests/PILOT-SESSION-STORAGE-001-sequential-write-identity-v2.md`, `APPROVED`
- Production change: clear the private anonymous-ID marker after its first successful publish

The marker exists only to prevent accepting a pre-existing collision for a new
anonymous candidate. Once that candidate is successfully published it is the
accepted current session. Clearing the marker makes later writes update the same
identity instead of allocating an orphan and leaving the cookie stale.

Fresh verification:

```text
PASS: PILOT-SESSION-STORAGE-001 v10 sequential write identity
PASS: PILOT-SESSION-STORAGE-001 v10 owner payload handoff
PASS: PILOT-SESSION-STORAGE-001 v10 accepted payload raw HTTP
PASS: PILOT-SESSION-STORAGE-001 v10 object payload raw HTTP
PASS: PILOT-SESSION-STORAGE-001 raw HTTP protocol tracer
PASS: PILOT-SESSION-STORAGE-001 v7 collision families
PASS: PILOT-SESSION-STORAGE-001 v7 DTO/fault tracers
PASS: PILOT-SESSION-STORAGE-001 v5 real-owner filesystem tracer
PASS: PILOT-SESSION-STORAGE-001 v7 config/revalidation/swap
PASS: PILOT-SESSION-STORAGE-001 v10 UserAccess flash owner handoff
PASS: PILOT-SESSION-STORAGE-001 v10 UserAccess action tokens
ARCHITECTURE CHECK PASSED (7 rules)
lint and diff-check: PASS
```

Independent Gate 5 remains required. Checklist LocalAuth request propagation is
a separate reviewed integration correction and is not claimed GREEN here.
