# OTIZ-SETTLEMENT-001 — revoked exact replay Gate 3 v1

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/settlement_review`
- Reviewed exact test candidate: `8b619471e88b58594862356aeee2667b51134341`
- Production baseline exposing the defect: `42001adc2bb8f2d89471f9d587199288e41fad40`
- Public seam: `OtizSettlement::completeSnapshotPayments()`
- Verdict: **CHANGES_REQUESTED**

The new case correctly creates a successful receipt, removes the actor's exact
current `otiz.manage` permission, and replays the identical actor, operation UUID
and fingerprint. Its independently derived result is `FORBIDDEN`: replay
stability does not waive the normative active/current-authority precondition.
The reported exit `255` at `revoked actor replayed prior success` is a genuine
missing-behavior RED against the Gate 4 candidate, not setup failure.

One blocking oracle gap remains. The test does not snapshot and compare persisted
facts across the revoked replay. The normative refusal contract requires no new
closure, event, or receipt. Because the setup already contains the successful
receipt being replayed, the correction must assert exact closure/event/operation
receipt counts before and after `FORBIDDEN`; otherwise a change that denies only
after an unintended append could pass.

Add the exact before/after fact-count assertion, retain the public `FORBIDDEN`
expectation, capture fresh RED for the corrected bytes, and regenerate/check the
plan. No production correction is authorized by this record.

Reviewed test hash:

```text
61b2a7b5f7eb8076129756ddb43c7f056870b61fdcfdee9d0f1b37f014d05761  tests/Otiz/settlement_owner_001_test.php
```

The plan was reported fresh at frozen candidate `8b619471`. A later check in the
shared branch is stale because independent review commits were added above that
candidate; this is not classified as an `8b619471` input defect.
