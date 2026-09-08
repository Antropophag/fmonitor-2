# PILOT-SESSION-STORAGE-001 v10 codec/request owner — Gate 4 GREEN

- Date: 2026-09-03
- Approved Gate 3:
  `reviews/tests/PILOT-SESSION-STORAGE-001-codec-request-owner-v3.md`
- Exact implementation commit:
  `2fcacc92303f8ac00ad9d3abb912b9cc4421fae2`

The codec now enforces the exact shared decode/encode grammar: canonical
byte-identity, allowed scalar types, no references/objects/floats, depth 16 and
4096 recursively totalled entries. Every LocalAuth, UserAccess and command
session write/regeneration passes through checked `encode()` before storage.

`PilotSessionRequestOwner::bind` accepts only the same object identity after the
first bind and throws on a conflicting injected graph, so explicit verifier
ports cannot be silently ignored.

Observed verification:

```text
codec/request-owner hardening test — PASS
all 23 pilot_session_storage*_test.php tests — PASS
PILOT-HTTP-AUTH-001 global calls — PASS
make architecture-check — ARCHITECTURE CHECK PASSED (7 rules)
quality graph validation — PASS
git diff --check — PASS
```
