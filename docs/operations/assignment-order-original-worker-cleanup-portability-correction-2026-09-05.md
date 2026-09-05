# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v54 — worker fixture cleanup portability correction

Append-only Gate 2 correction evidence for the approved worker-transport
executable. This is a test-fixture cleanup correction only. It does not change
an oracle, product behavior, production code, or the timing of any assertion.

## Before

Base SHA: `75037bd4210561aac78b509c0c3e90b488c39dbd`.

The executable completed its behavioral assertions, then its `finally` blocks
used `rmdir()` on nonempty private-storage roots. The observed run emitted
`Directory not empty` warnings for the main `private`/control roots and both
`isolated-identical` and `isolated-different` roots. The leaked trees retained
runtime-created dotfiles including `.aoou-state.lock`, `.aoou-state.json`, and
the content-lease `.lock-4cd706c4bcaf0a5fc96ade0bffe9ddef09293c23c73a417ecfda3506b47100f6`.

The pre-correction executable nevertheless reached:

```text
ASSIGNMENT_ORDER_ORIGINAL_WORKER_TRANSPORT_OK
RED_ASSERTION: expected failure but tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php passed
```

Pre-correction hashes:

```text
13dedd0a6d8b06e419b3d5c61e690d637768899b0e845f11584b4b55538cee1c  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
8a88d3f93da76186296610c8d54567d1b00d3a81ccb311f25b16ebd584318fdd  tests/Support/assignment_order_original_isolated_races.php
```

## Correction

The worker executable now owns one bounded recursive remover. Its public entry
accepts only the exact random control root `aoou-worker-<128-bit-token>` or the
two `isolated-*` descendants constructed beneath that captured root. Symlinks
and files are unlinked; directories are traversed child-first. Both included
isolated-race fixtures and the outer fixture invoke it only from `finally`,
after all behavioral and retained-content assertions.

## After

Commands:

```text
$ php -l tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
$ php -l tests/Support/assignment_order_original_isolated_races.php
No syntax errors detected in tests/Support/assignment_order_original_isolated_races.php
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_WORKER_TRANSPORT_OK
RED_ASSERTION: expected failure but tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php passed
```

No cleanup warning was emitted, and no newly created `aoou-worker-*` root
remained after the corrected run. The wrapper status remains expected because
the current production implementation is GREEN. No RED is invented: this
record classifies the change solely as cleanup portability correction.

Corrected hashes:

```text
9592b2be288815d7040bfb65cdf33ae0d0b2f49cc38a0cb420d1ec05e4c6f82e  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
2d75e683755f1c444b21d0e57f512ffee3265b044e1c00dd766f4b1d78c473fa  tests/Support/assignment_order_original_isolated_races.php
```

Fresh independent Gate 3 review is required for these exact corrected fixture
hashes before the executable can be used as reviewed evidence.
