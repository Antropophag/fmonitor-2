# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 production safe-log — Gate 2 RED correction v56

- Date: `2026-09-05`
- Test author: separately tasked agent `/root/safe_log_red_v55`
- Prior RED commit: `95625ba0db227dd03f4119303a5ea78f50d80cd5`
- Gate 3 `CHANGES_REQUESTED`: `e21565cc567d9783e433b1cd7a1c37ab4c242eb8`
- Approved executable-spec commit: `bcdaedb7cd7cb7b80684d29f432a3d20a40e0177`
- Corrected test SHA-256: `46f24422011c0493595f3f690a2839753a43e4cab427e255faa2beaf4e912a81`

## Correction

The ambient `/etc/master.passwd` oracle was removed. The test creates its own
regular `0600` sentinel, mounts it read-only into the pinned local test image,
copies it as container root, and then runs the public factory probe under exact
effective UID/GID `65534`. Before factory construction the child proves with
`lstat` that the configured entry exists, is regular, is not a symlink, has
exact mode `0600`, and has UID different from its effective UID. The parent
created and hashed the sentinel bytes; the read-only bind prevents mutation of
that source. Failure to establish the distinct-UID capability exits `70` and is
reported as `SETUP_FAILURE`, never as wrong-owner behavioral RED. No case can
collapse into the separate missing-file oracle.

## Fresh intended RED

Command:

```text
php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
```

Result: exit `255`, test-owned `INTENDED_RED`. The corrected relevant line is:

```text
production factory safe log wrong-owner: accepted
```

The remaining intended failures are unchanged: missing mandatory third field,
discarded production diagnostic, acceptance of missing/relative/non-canonical/
symlink/non-regular/wrong-mode/device paths, and the non-canonical exception
shape proving private-root-first validation. There was no `SETUP_FAILURE`.

Mechanical checks:

```text
$ php -l tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php

$ git diff --check
(no output; exit 0)
```

This is corrected Gate 2 evidence only. The author does not review the test; a
fresh independent Gate 3 review is required.
