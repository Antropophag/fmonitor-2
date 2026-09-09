# Independent Gate 3 review — PRODUCTION-RUNTIME-STORAGE-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Test author: separately tasked agent `/root/runtime_tests`
- Verdict: **APPROVED (BOUNDED)**

This approval covers storage prepare and filesystem read-only checks only. It
does not approve the full production runtime.

## Exact reviewed tests

```text
1ca9da7c730eabbaa0fea38619de6079bbcd90517331ef2c3cf1162124110955  tests/Runtime/runtime_storage_001_test.php
15120d98acd511aca5c90c05708cf4199f2c46568df19775de5a24e83d91c307  tests/Runtime/production_runtime_compose_001_test.php
```

## Coverage and sensitivity

The native CLI test uses a unique real temporary root and the effective invoking
UID/GID. It requires fresh private directories, exact modes, a no-newline injected
credential, empty safe log, and successful idempotent replay that preserves bytes,
modes, mtimes, inodes, UID and GID. It snapshots filesystem state around readiness.

Unsafe fixtures cover a symlink root, changed secret bytes, permissive credential
mode, and wrong log type; each must return stable exit 70 JSON with empty stderr and
preserve the complete filesystem snapshot. The real Compose test separately proves
production UID/GID `10001:10001` and modes for all directories and both files. Its
root-created wrong-owner credential must be rejected without changing owner or
bytes before the fixture restores ownership.

This combination validates the effective-identity native contract and fixed
production identity without requiring privileged host test execution.

## Demonstrated RED

```text
$ php -l tests/Runtime/runtime_storage_001_test.php
No syntax errors detected in tests/Runtime/runtime_storage_001_test.php

$ php tests/Runtime/runtime_storage_001_test.php
Fatal error: Uncaught TestFailure: INTENTIONAL_RED: runtime storage prepare CLI exists
Expected: true
Actual: false
exit 255
```

The failure is the intended absence of `bin/fmonitor2-runtime-prepare.php`, before
any filesystem setup dependency. The readiness CLI absence is an independent next
barrier.

## Gate decision

**APPROVED (BOUNDED).** Gate 4 may implement the prepare CLI and filesystem portion
of the readiness CLI to satisfy these exact tests. Database/schema readiness,
HTTP health, full restart persistence, and graceful stop remain outside this
approval and in the pending full-runtime Gate 3 record.
