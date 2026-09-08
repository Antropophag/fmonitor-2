# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 worker-cleanup portability review v1

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/worker_cleanup_gate3`
- Correction author: separately tasked agent other than this reviewer
- Reviewed commit: `bad123c7ad6ab565f8a451c1a127bb0c347c085d`
- Reviewed base: `75037bd4210561aac78b509c0c3e90b488c39dbd`
- Contract: approved `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` worker transport
- Verdict: **APPROVED**

The reviewer did not author the executable, cleanup correction, production
implementation, or correction evidence. This append-only review record is the
only artifact authored by the reviewer.

## Scope and findings

The reviewed commit changes exactly two test-fixture files plus its append-only
Gate 2 correction record. It changes no file under `app/` and therefore changes
no production seam or product behavior.

The executable owns one recursive remover. Its only accepted roots are the
captured `aoou-worker-<16-random-byte hexadecimal token>` control root and
`isolated-*` roots beneath that control root. Call sites pass only the exact
captured control root or the two fixture-constructed `isolated-identical` and
`isolated-different` roots. Traversal skips `.` and `..`, unlinks regular files
and symlinks instead of following them, and visits directory children before
removing their parent. This safely removes runtime dotfiles while bounding the
operation to this invocation's uniquely named fixture tree.

Both isolated-race cleanup calls remain in their per-race `finally` after the
complete READY, result-channel, and literal persisted-inventory assertions.
The outer cleanup remains in the executable's final `finally`, after the main
transport, persistence, race, and retained-content assertions and after the
reader/database lifecycle cleanup. No assertion, expected value, command
frame, worker scheduling step, public seam, or behavioral oracle moved or was
weakened. A plausible persistence, transport, race, or channel regression
therefore still fails before teardown; `finally` only prevents fixture leakage.

## Independent reproduction

Commands and results:

```text
$ php -l tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
$ php -l tests/Support/assignment_order_original_isolated_races.php
No syntax errors detected in tests/Support/assignment_order_original_isolated_races.php
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_WORKER_TRANSPORT_OK
RED_ASSERTION: expected failure but tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php passed
```

The RED wrapper exited `1` because current production makes the approved test
GREEN; this correction does not claim or manufacture RED. Captured stderr was
exactly the wrapper's 125-byte `RED_ASSERTION` diagnostic above: it contained
no PHP warning and no cleanup failure. A sorted, unique inventory of immediate
`aoou-worker-*` roots under `/tmp` and `/private/tmp` was captured before and
after the run; `diff -u` produced no output and the post-run inventory was
empty. `git diff --check bad123c^ bad123c` also produced no output.

## Gate decision

The correction is teardown-only, bounded to uniquely generated task-owned
roots, dotfile- and symlink-safe, positioned after the relevant behavioral and
persistence assertions, and independently reproduced without warnings or new
leaked roots. Behavioral sensitivity and all approved worker-transport oracles
are preserved. Gate 3 is **APPROVED** for the exact reviewed hashes below.

## Exact reviewed hashes

```text
9592b2be288815d7040bfb65cdf33ae0d0b2f49cc38a0cb420d1ec05e4c6f82e  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
2d75e683755f1c444b21d0e57f512ffee3265b044e1c00dd766f4b1d78c473fa  tests/Support/assignment_order_original_isolated_races.php
db910670481058fddd1bd2556843578e22c2fc4bff3683a172e415fe39c7c1b8  docs/operations/assignment-order-original-worker-cleanup-portability-correction-2026-09-05.md
```
