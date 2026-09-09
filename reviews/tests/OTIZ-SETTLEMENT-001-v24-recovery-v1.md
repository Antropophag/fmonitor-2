# OTIZ-SETTLEMENT-001 — canonical v24 recovery Gate 3 v1

- Date: `2026-09-10`
- Reviewer: separately tasked agent `/root/settlement_review`
- Specification/test author: root
- Reviewed exact candidate: `a0f2b352`
- Verdict: **CHANGES_REQUESTED**

The current-v24 recovery oracle is otherwise strong. It requires exact
24/71/39 inventory, seeds settlement receipt/lock/snapshot/object/closure/event
facts, compares all domain rows plus Jobs rows/AUTO/private state across
backup/restore, rejects corrupt version/table/AUTO manifests before target
mutation, and retains runtime DDL denial and no-repeat domain behavior. The v22
forward drill builds an exact historical source image, restores its literal
22/63/35 bundle, preserves rows/AUTO/private bytes through migrations23/24, and
requires old tooling to reject the v24 bundle.

The captured RED is valid. In a real runtime dependency image with current
checkout mounted read-only, canonical24 migration and RuntimeReadiness succeed;
public backup then returns exit70/BACKUP_FAILED instead of exit0/BACKUP_CREATED
because production recovery remains bound to v23. The image contains the real
database/tar dependencies, and cleanup is inherited from the established
harness. Plan validation returned `CHANGE_VERIFICATION_OK`.

One blocking mapping gap remains. The new normative contract explicitly keeps
both historical v22 and v23 profiles immutable and says old bundles use their
exact source image before forward migration. The amendment exercises only an
exact v22 image. `runtime_jobs_recovery_001_test.php`, formerly the current-v23
case, is advanced to current v24, leaving no executable exact-v23 image/profile
rehearsal.

Add a bounded exact-v23 source/image case that proves its literal 23/69/39
bundle can be restored by v23 tooling, forward-migrated to v24 with all existing
rows/AUTO/private bytes preserved, and rejected by v23 tooling when given a v24
bundle. Sharing the established v22 forward helper is acceptable. Alternatively
bind an existing executable test if one exists; repository search found none.

Reviewed identities:

```text
e788540951d3a617b95f25d137d615b103bb6b09e0f1157c6cc6835eab349578  specs/OTIZ-SETTLEMENT-001.md
fe2b10b4cfdc889516c4e388a56c5a1d8b39c5a14967a3bb5a695ca80c7f6b2c  tests/Runtime/runtime_jobs_recovery_001_test.php
184c67cd455b4d30f7bbf0c74350201c7a45ca09cdcd9dd373403c32bbec52d9  tests/Runtime/runtime_recovery_forward_update_001_test.php
0c3a937be59dd6425dbcc44c7238c63b6ec058587398c642ffea1c4c087b2f22  docs/operations/otiz-v24-recovery-red-2026-09-10.md
```

Recovery Gate 3 remains **CHANGES_REQUESTED**. No production recovery change is
authorized until exact-v23 history coverage is added and independently reviewed.
