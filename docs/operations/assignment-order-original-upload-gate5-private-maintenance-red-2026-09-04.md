# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — private storage/maintenance RED

- Baseline: `99ba4d7719aef6e23e2ea4e334f95981b826ad2d`
- Result: **INTENDED RED**

The focused public test creates verifier-owned real filesystem stages and
finalized content through `AssignmentOrderOriginalPrivateStorageFactory`. It
requires age-filtered deterministic pagination/cursor inventory, approved
canonical inventory JSON, and runs the real maintenance application with the
real storage plus controlled authorization/reference/request ports. It proves
digest locking, reference recheck, deletion, same-request replay, young-item
retention, and corrupt existing digest rejection without a lease.

Actual command exits `255`:

```text
php tests/InstallationProcess/assignment_order_original_upload_001_private_storage_maintenance_test.php
Batch limit bounds first page.
Expected: 1
Actual: 0
```

This is the intended Gate 5 disagreement: production `listOrphans()` currently
returns an unconditional empty page. PHP lint passes and `git diff --check` has
no output. Owned artifacts are removed in `finally`; production is untouched.
