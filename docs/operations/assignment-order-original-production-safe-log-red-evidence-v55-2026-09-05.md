# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v55 production safe-log — Gate 2 RED evidence

- Date: `2026-09-05`
- Test author: separately tasked agent `/root/safe_log_red_v55`
- Approved executable-spec commit: `bcdaedb7cd7cb7b80684d29f432a3d20a40e0177`
- Independent Gate 1 approval commit: `b510efc57b7dc4f7c3965f0b98e33bcfcedb721c`
- RED parent HEAD observed before commit: `fba48d1686aa8b1d9d8f73840c3961af130a88f1`
- Executable test SHA-256: `ad1db8a37f5c3413746548cd4a22ce99e5a5bb7e46b4f015aadacc12d8d9f437`
- Approved specification SHA-256: `4cae80e141ad4caf758e792d0ae5a8383c32f6b9d6a779d6eace6122ec71b255`

## Scope

Only `tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php`
was amended. The public seam remains
`ProductionAssignmentOrderOriginalFactory::create(mysqli, config)` followed by
the returned `AssignmentOrderOriginalApplication` command seam. The test now
proves the mandatory exact third `safeLogFile` config field; existing absolute
canonical non-symlink regular effective-user-owned exact-`0600` acceptance;
missing, relative, non-canonical, symlink, non-regular, wrong-mode, wrong-owner,
and device rejection; no create/repair/replace; fixed redacted construction
exception shape; validation before a database-operation sentinel and before a
missing private-storage sentinel; and exact append-only diagnostic bytes through
the configured production application. Existing worker and evidence-reader
configuration contracts are unchanged.

## Demonstrated RED

Command:

```text
php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
```

Exit: `255` with the intended test-owned marker and these relevant failures:

```text
INTENDED_RED: approved production boundary is incomplete:
production config: mandatory exact third safeLogFile field absent
production factory cleanup probe: exact safe log append absent
production factory safe log missing: accepted
production factory safe log relative: accepted
production factory safe log non-canonical: accepted
production factory safe log symlink: accepted
production factory safe log non-regular: accepted
production factory safe log wrong-mode: accepted
production factory safe log wrong-owner: accepted
production factory safe log device: accepted
production factory validates safe log before database/private storage: non-canonical exception shape RuntimeException message="" code=0 previous=null
```

This is behavioral RED, not setup failure: the MariaDB fixture, real production
factory application, accepted PDF persistence, evidence reader, private storage,
and cleanup all ran. The failures specifically expose the current two-field
config, discarded production logger, absent validation matrix, wrong exception,
and private-root-before-safe-log ordering. The database sentinel remained at
zero calls and the absent private root was not created; the exception shape
still detects the ordering violation.

## Mechanical checks

```text
$ php -l tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php

$ git diff --check
(no output; exit 0)
```

This record is Gate 2 evidence only. The author does not review or approve the
test. A fresh separately tasked Gate 3 review is required before production
implementation.
