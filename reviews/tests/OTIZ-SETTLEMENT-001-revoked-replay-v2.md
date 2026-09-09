# OTIZ-SETTLEMENT-001 — revoked exact replay Gate 3 v2

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/settlement_review`
- Reviewed exact candidate: `246b6860d6462368fd91f2cc593235062edab95d`
- Correction baseline/review: `c5454633`
- Public seam: `OtizSettlement::completeSnapshotPayments()`
- Verdict: **APPROVED**

The sole v1 finding is closed. Immediately after revoking the actor's exact
`otiz.manage` permission, the test snapshots closure, event, and operation
receipt counts. It requires public `FORBIDDEN` for identical replay and exact
count equality afterward. Thus authorization precedence and refusal-without-new-
facts are both observable while the prior successful receipt remains present.

Independent reproduction on the exact candidate:

```text
$ php tests/Otiz/settlement_owner_001_test.php
PHP Fatal error: Uncaught TestFailure: revoked actor replayed prior success
... tests/Otiz/settlement_owner_001_test.php:46
exit 255

$ python3 tools/delivery/change-verification.py check \
    --plan .local/verification/otiz-settlement-owner-plan.json
CHANGE_VERIFICATION_OK
```

The failure occurs after successful canonical setup, successful original
commands, and authorized stable replay. It is the intended leaked-success defect,
not setup failure. `git diff --check c5454633..246b6860` passed.

Reviewed test identity:

```text
286ccd38aac0825cc66aae4760de27271dd0a10fee8df1dfe1993c6558e84c0b  tests/Otiz/settlement_owner_001_test.php
```

Gate 3 for the revoked exact-replay increment is approved. Gate 4 may make only
the minimal production correction that preserves current authorization before
receipt recovery, without changing approved expectations. Focused GREEN and the
remaining HTTP/adapter work still require verification and final Gate 5.
