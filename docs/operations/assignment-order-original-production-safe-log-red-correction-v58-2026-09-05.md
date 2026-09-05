# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 production safe-log — Gate 2 RED correction v58

- Date: `2026-09-05`
- Test author: separately tasked agent `/root/safe_log_red_v55`
- Prior corrected RED commit: `90f9383facf4aaee91b602b02008e9dd58333085`
- Gate 3 `CHANGES_REQUESTED`: `ef42cb50751dbe675282fa2e72731c606f0cce27`
- Approved executable-spec commit: `bcdaedb7cd7cb7b80684d29f432a3d20a40e0177`
- Corrected test SHA-256: `09897fefe895c7aec19d2991b2237ab0996c00a1a5595051218334e05eb87907`

## Sole correction

The bounded Docker coordinator now reapplies the `4096`-byte stdout/stderr
limit immediately after every final-drain append. Overflow throws explicit
`SETUP_FAILURE`; the existing `finally` still performs exact child TERM/KILL,
pipe closure, and reap before that failure escapes. A defensive length check at
the beginning of `finally` remains as a second guard. No UID fixture, semantic
protocol, public-seam expectation, or other RED assertion changed.

## Fresh intended RED and cleanup

```text
$ php -l tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php

$ php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
INTENDED_RED: approved production boundary is incomplete:
...
production factory safe log wrong-owner: accepted
...
```

The focused test exited `255` with the same eleven intended product gaps and no
`SETUP_FAILURE`. Immediately afterward both residue checks produced no output:

```text
docker ps --filter ancestor=fmonitor2-php-test:latest --format '{{.ID}} {{.Status}} {{.Names}}'
find "${TMPDIR:-/tmp}" -maxdepth 1 -name 'aoou-production-boundary-*' -print
```

`git diff --check` also produced no output and exited `0`.

This is corrected Gate 2 evidence only. The author does not review the test; a
fresh independent Gate 3 review is required.
