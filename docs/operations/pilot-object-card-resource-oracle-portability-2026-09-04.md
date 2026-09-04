# PILOT-OBJECT-CARD resource-release oracle portability

Date: `2026-09-04`

Initial independent review: `6f0aad9` (`NEEDS_CHANGES`).

## RED

The public object-card verifier required `/proc/<pid>/fd` and
`/proc/net/tcp*`. Native macOS therefore failed at the observer setup before it
could assess a response or resource lifetime. The unchanged test was GREEN in
Linux, proving a fixture portability problem rather than a production failure.

## Corrected Gate 2 candidate

Commit: `88fe01f49905d590c90b1b341ed0c02a4685a18e`

Test SHA-256:
`c9b151d7fe12d12e986f2d3c93fae0cc5d5648529272f8975de681ec155b77f3`.

The test now requires a live worker and enumerates exact descriptor paths via
Linux procfs or absolute Darwin `/usr/sbin/lsof -a -p <pid> -Fn`. Unsupported
platforms, missing observers and process failures fail setup; there is no skip
or empty fallback. It parses only machine `n<path>` records and compares the
configured CSS path byte-exactly.

The DB oracle now directly requires zero `PROCESSLIST` rows for the exact
escaped random test database and random test-created reader principal. This is
stronger and more portable than reconstructing only worker sockets from procfs.

Sensitivity controls prove both observers:

- a deliberately open task-owned CSS descriptor is visible, then absent after
  close;
- a deliberately open unique reader connection yields exactly one matching
  process row, then zero after close.

Full public HTTP matrix results:

```text
native macOS arm64: PASS: PILOT-OBJECT-CARD-001 public HTTP card
Linux arm64, disposable copy, non-root task user: PASS: PILOT-OBJECT-CARD-001 public HTTP card
```

PHP lint and diff-check pass. No production or specification file changed.
Fresh independent Gate 3 remains required.
