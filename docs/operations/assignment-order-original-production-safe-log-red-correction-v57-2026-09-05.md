# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 production safe-log — Gate 2 RED correction v57

- Date: `2026-09-05`
- Test author: separately tasked agent `/root/safe_log_red_v55`
- Prior corrected RED commit: `f0897214f7f52d7758cac69bfe6b9caaee3830c2`
- Gate 3 `CHANGES_REQUESTED`: `18ddd12f80ffe7a1873a0940a26d3532b94dffbe`
- Approved executable-spec commit: `bcdaedb7cd7cb7b80684d29f432a3d20a40e0177`
- Corrected test SHA-256: `b64ea32ddfdad6bc38fc7dc4c83da5ddf566941f7179a4c9f1027bcaf4676641`

## Correction

The controlled distinct-owner Docker probe now runs through a bounded parent
coordinator. Both output pipes are nonblocking, each read/output accumulation
is bounded, and a ten-second monotonic deadline bounds the child. Its `finally`
path always attempts TERM, then KILL after a one-second monotonic grace period,
closes every pipe, and calls `proc_close` to reap the exact child. Timeout,
launch failure, pipe failure, excessive output, inability to terminate/reap,
exit `70` precondition failure, Docker infrastructure/signal exits, and every
unrecognized exit/output tuple are explicit `SETUP_FAILURE`.

Only three exact semantic protocols are accepted: canonical rejection
(`exit 0`, empty output), current permissive behavior (`exit 1`, exact
`WRONG_OWNER_ACCEPTED` line), and a non-canonical PHP exception (`exit 2`, one
class-name line). The distinct UID/regular/non-symlink/exact-`0600` child proof
and separate missing-file oracle remain unchanged.

## Fresh intended RED

```text
$ php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
INTENDED_RED: approved production boundary is incomplete:
...
production factory safe log wrong-owner: accepted
...
```

Exit was `255`; there was no `SETUP_FAILURE`. The other ten intended production
gaps remain unchanged. After the run, this command returned no rows, proving no
probe container remained:

```text
docker ps --filter ancestor=fmonitor2-php-test:latest --format '{{.ID}} {{.Status}} {{.Names}}'
```

Mechanical checks:

```text
$ php -l tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php

$ git diff --check
(no output; exit 0)
```

This is corrected Gate 2 evidence only. The author does not review the test; a
fresh independent Gate 3 review is required.
