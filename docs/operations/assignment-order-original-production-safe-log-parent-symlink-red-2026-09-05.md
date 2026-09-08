# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 safe-log parent symlink — Gate 2 RED

- Date: `2026-09-05`
- Test author: separately tasked agent `/root/safe_log_red_v55`
- Gate 5 findings: `3be68a81a220cf24c001f4f2bdaeda06e305625a`
- Exact implementation under test: `813d224ae4ba99a8d685fcfce48d161b27e7a3e4`
- Corrected production-boundary test SHA-256: `513d315779988ef87f93b175cddd652188d33a5c2665f2ac4af667e62d526a53`

The task-owned fixture creates a real parent directory containing an existing
effective-user-owned regular exact-`0600` file, plus a sibling symlink to that
parent. The configured file entry itself is not a symlink, but its configured
absolute spelling differs from `realpath`. Through the public production
factory seam the test requires the fixed redacted production-configuration
exception.

```text
$ php -l tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php

$ php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
INTENDED_RED: approved production boundary is incomplete:
production factory safe log symlinked parent: accepted
```

Exit was `255`. No setup failure occurred, the random task-owned root was
removed in `finally`, and `git diff --check` was clean. This is Gate 2 evidence
only and requires fresh independent Gate 3 review.
