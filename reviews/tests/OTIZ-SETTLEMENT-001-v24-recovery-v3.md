# OTIZ-SETTLEMENT-001 — canonical v24 recovery Gate 3 v3

- Date: `2026-09-10`
- Reviewer: separately tasked agent `/root/settlement_review`
- Test/mapping author: root
- Reviewed recovery correction: `978739fe`
- Prior review: `reviews/tests/OTIZ-SETTLEMENT-001-v24-recovery-v2.md`
- Verdict: **APPROVED**

The sole v2 finding is closed. The verification acceptance seam now explicitly
names historical exact v22/v23 images followed by current canonical migration,
matching the accepted contract and the two-profile executable rehearsal. The
common build diagnostic now says exact historical image/version rather than v22.
No recovery behavior, expected value or RED evidence changed.

The v22/v23 exact-source/image/OCI, own-image restore, forward24 row/AUTO/private
state preservation and old-image v24 rejection coverage reviewed in v2 remains
sound. The saved plan was regenerated post-commit and reported
`CHANGE_VERIFICATION_OK`. A later check in the shared branch is stale after
independent review and parallel locked-runtime commits; this approval binds the
exact recovery input/test hashes rather than treating that later state as a
candidate defect.

Reviewed corrected identities:

```text
905df1ee9cf6c457121450ba8dc848476f139e33f80bf84f0a373949811b9c7d  openspec/changes/otiz-settlement-owner/verification-input.json
a03f4430c8f4c4029841474fe313aebc9b8d3d307acec47ef56fb027017586bb  tests/Runtime/runtime_recovery_forward_update_001_test.php
```

Canonical v24 recovery Gate 3 is **APPROVED**. Minimal implementation may update
only the reviewed current recovery schema/adapter boundaries. Runtime image
compatibility, UI, removal, focused GREEN, final Gate 5 and CI remain governed by
their separate approvals.
