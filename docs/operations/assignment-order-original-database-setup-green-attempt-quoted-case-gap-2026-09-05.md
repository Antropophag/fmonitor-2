# Assignment-order original setup — quoted literal case-sensitivity gap

Date: `2026-09-05`

Status: **GATE 2 RESTART REQUIRED**.

The full boolean canonicalizer lower-cases the complete CHECK source before
parsing. That includes quoted SQL/regex literals. Consequently the deliberately
wrong hash constraint `[0-9A-F]{64}` canonicalizes to the approved
`[0-9a-f]{64}` and the migration reports `UNCHANGED` instead of `CONFLICT`.

Keywords and unquoted identifiers are case-insensitive; quoted literal bytes
are semantic and must remain byte-exact. Production is preserved outside the
repository and removed from the worktree. Task 2.2 is reopened. RED author must
case-fold only tokens outside quoted literals and add executable mixed-case
keyword/identifier equivalence plus quoted-literal case mutation sensitivity
before a fresh Gate 3.
