# Gate 3 test review: production safe-log parent-component symlink v60

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/safe_log_parent_gate3`
- Exact reviewed RED commit: `33e94ae4ea28f46098fb50c09d925bce1182ef3a`
- Source Gate 5 findings: `3be68a81a220cf24c001f4f2bdaeda06e305625a`
- Exact implementation under test: `813d224ae4ba99a8d685fcfce48d161b27e7a3e4`
- Verdict for the parent-component symlink test only: **APPROVED**

## Independence and scope

I did not author the v55 specification/planning artifacts, production code,
production-boundary test, or Gate 2 evidence. I reviewed only the corrective
public-factory RED for `G5-SAFELOG-1`. I did not edit tests, specifications,
planning artifacts, or production.

This verdict does not approve Gate 4, the production safe-log implementation,
the complete production-boundary suite, or the complete original-upload
command. In particular, `G5-SAFELOG-2` descriptor-integrity observability is an
explicitly separate Gate 1 gap and is outside this test approval.

## Exact reviewed artifact identities

```text
4cae80e141ad4caf758e792d0ae5a8383c32f6b9d6a779d6eace6122ec71b255  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
09fbfb8d9a5405989ad40150d011b557d8fea1ba030530fbf68ce06c3a710abb  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
482fb11555e9f10968a2485c2f22e863543c67fa78c41cd8148ce3f1b0e4ce09  openspec/changes/replace-pilot-registration-with-original-upload/design.md
42a03479da33ecc99f2b6e6f76600480a723e7a8c8e25296d33285584d8d3c88  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
dd4c5f05ddfadfcff57a8f4303165a806a43a4b339227407ceac6a37b8d7d2b2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
513d315779988ef87f93b175cddd652188d33a5c2665f2ac4af667e62d526a53  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
067acfd0bb83f05cb73a7c04e22b8001a91898c8c500737a1dbd9e142072d43a  docs/operations/assignment-order-original-production-safe-log-parent-symlink-red-2026-09-05.md
98f99e8cc7201725225e5c4d2bc41c61905e606bbb776287782f66fee1e4c693  docs/operations/assignment-order-original-production-safe-log-descriptor-race-gate1-gap-2026-09-05.md
```

## Review

The v55 contract requires the production `safeLogFile` input to be the exact
absolute canonical existing path, not a spelling that becomes canonical only
after traversing a symlink. The added fixture creates a random task-owned real
parent, an existing current-EUID-owned regular exact-`0600` `safe.log` within
it, and a sibling symlink to that parent. The final file entry is deliberately
not itself a symlink. This isolates the missing intermediate-component check
from the already-covered final-entry symlink, missing, type, owner and mode
cases.

The assertion uses the public production seam
`ProductionAssignmentOrderOriginalFactory::create(mysqli, config)` and
requires the exact fixed redacted
`AssignmentOrderOriginalProductionConfigurationUnavailable` class/message/
code/previous shape. It is sensitive to the reviewed defect: the current
factory canonicalizes the configured spelling to `realpath` before constructing
the logger and therefore accepts it; a conforming validator that requires
configured-path equality with its resolved identity makes this one assertion
pass. No verification-only hook, production selector, or private validator is
used.

Setup and cleanup are deterministic and bounded. The fixture creates all of
the real parent, target file, and alias inside the existing test's random
`aoou-production-boundary-<token>` root. Its outer `finally` closes database
handles, drops only the random database, and recursively removes only that
random root without following the alias (the remover treats a symlink as a
leaf). A post-run search found no matching task-owned artifact. The fixture
does not use production secrets, repository fixtures, real documents, or
personal data.

## Independent RED reproduction

On exact reviewed commit `33e94ae4ea28f46098fb50c09d925bce1182ef3a`:

```text
$ php -l tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php

$ php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved production boundary is incomplete:
production factory safe log symlinked parent: accepted
```

Exit was exactly `255`; there was no setup-failure line and no additional
production mismatch. `git diff --check` produced no output.

## Decision

Gate 3 is **APPROVED** only for the parent-component symlink RED at
`33e94ae4ea28f46098fb50c09d925bce1182ef3a`. A separately assigned Gate 4
implementer may make the minimum production correction for `G5-SAFELOG-1`
without changing this test/spec/config. Gate 4 overall is not approved:
`G5-SAFELOG-2` remains blocked before Gate 2 by its recorded descriptor-
integrity Gate 1 observability gap and requires its own approved sequence.
