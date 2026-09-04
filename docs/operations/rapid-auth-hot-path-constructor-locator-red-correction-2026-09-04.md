# Rapid auth hot path — constructor locator RED correction

Date: 2026-09-04

RED author: `/root/original_upload_red`

Verdict: **CORRECTED TEST; fresh independent Gate 3 required**

## Controlling approved evidence

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
a2e376531a4db9364cc16636388d9bc8285bd54b06d16ddd8b68edd6f0818496  reviews/tests/PILOT-SESSION-STORAGE-001-local-auth-lifecycle-v1.md
1abbf879022d43d2e85bc4bfcd1ae8845fe46c09c8c7768fb9e8c4f0013c354e  reviews/tests/PILOT-SESSION-STORAGE-001-architecture-ratchet-v2.md
```

## Correction

The verifier previously searched only literal `public function __construct()`.
Current approved `RapidPilotLocalAuth` has exact optional typed signature
`public function __construct(?FMonitor\IdentityAccess\PilotSessionStorage
$storage=null)`. The locator now requires that exact type, nullable marker,
parameter and null default; constructor absence and arbitrary signatures remain
rejected. It extracts only the body between that exact constructor and the
following public `handle` boundary.

Existing assertions remain intact: constructor DDL, bulk `INSERT`, constructor
`ensureSchema`, private `ensureSchema`, identity-bootstrap ownership and all
other auth/queue/runtime assertions. A sensitivity probe injects literal
`CREATE TABLE` into the extracted constructor body and requires the exact DDL
failure; the verifier cannot print PASS unless that mutation is rejected.

## Verification

Old verifier on exact `80813dc`:

```text
$ php -l rapid-pilot/verify-auth-hot-path.php
No syntax errors detected in rapid-pilot/verify-auth-hot-path.php
$ php rapid-pilot/verify-auth-hot-path.php
RuntimeException: LocalAuth constructor unavailable
exit 255
```

This is a false RED caused only by the stale locator.

Corrected verifier against the same production revision:

```text
$ php -l rapid-pilot/verify-auth-hot-path.php
No syntax errors detected in rapid-pilot/verify-auth-hot-path.php
$ php rapid-pilot/verify-auth-hot-path.php
PASS auth hot path is schema-mutation free
$ git diff --check
PASS (no output)
```

The corrected PASS includes the injected-constructor-DDL mutation probe; if the
probe is not rejected with `request-time auth constructor contains DDL`, the
verifier itself fails before PASS. This is diagnostic test-correction evidence,
not a new production GREEN or Gate 4 claim. Production code was not changed.
