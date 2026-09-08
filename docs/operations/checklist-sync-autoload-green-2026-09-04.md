# Checklist projection reader autoload GREEN

Date: `2026-09-04`

Gate 3: `dae7d2d` (`APPROVED`)

Production candidate: `56ffd66759b349cd9cefa748b81dd7481311da4c`

The only production change makes the direct
`MariaDbChecklistProjectionStateReader` collaborator loadable from the same
`ChecklistSync.php` composition owner. Tests, SQL, domain behavior and
rapid-pilot verifiers are unchanged.

Verification:

```text
Checklist current crew contract OK.
ok - CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001 oracle is deterministic and isolated
ok - CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001 oracle is deterministic, audited, isolated, and correctly classified
ok - CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001 public oracle is deterministic, isolated, and correctly classified
ok - CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001 public oracle is deterministic, isolated, and correctly classified
ARCHITECTURE CHECK PASSED (7 rules)
No syntax errors detected in app/PilotHttp/ChecklistSync.php
```

The four DB-owning meta-verifiers were run sequentially because each asserts
whole-schema isolation against concurrent foreign mutations.

The aggregate `make characterization-test` now reports exactly one verifier
failure: the already classified macOS host lacks the `setsid` executable used
by `characterize_inspection_schedule_duplicate_001_test.php`. All checklist
characterization paths pass in that aggregate run.

Fresh independent Gate 5: `0f15cb892db7e4902273938eb2a4248f6f855bed`
(`APPROVED`). The reviewer independently reproduced all five GREEN paths,
architecture `7/7`, PHP lint, dependency loading and diff-check. This focused
integration correction is complete.
