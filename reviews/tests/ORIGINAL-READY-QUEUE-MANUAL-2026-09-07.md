# Original-ready queue reconciliation — independent test review

Reviewer: `/root/photo_review`; artifact author: `/root`.
Verdict: **APPROVED**.

The test reproduces the owner-visible mismatch through public selection and original
application seams: an accepted current original makes both card semantics and queue
status/filter/count ready without a separate application. It also proves the inverse:
a new pending composition makes the prior original and prior application stale for
readiness while retaining both histories byte-for-byte. Unfiltered, positive and
opposite filters exercise the same object, count and page metadata, and every queue
read is bracketed by complete fixture DB/private-file snapshots.

The initial review found an uncovered prior-application/new-selection case. Its RED
raised `PilotHttpInfrastructureUnavailable` after SQL count included the object but
row labeling rejected it. The final test includes that case and is sensitive to the
corrected label behavior.

Exact reviewed test:

```text
052c8ef9442c15c4151cfe787b850cd29523d0f088a6a5ad9174be6180a73149  tests/AssignmentOrderComposition/original_ready_queue_manual_test.php
```

Independent focused execution PASS. Author evidence: initial owner-scenario RED
SHA-256 `3610d4a086bedc8f6191129ca1e2dbd007176381bec29877054076ff3adb2489`;
supplemental stale-application RED SHA-256
`4e22449a2c7dfd34b5c33b6b8293f52e8484190c06feb6d2b0d6bc9d4e226cc0`;
final GREEN SHA-256 `1916d1d7e393f4b7e989c709a604376c2722b8e5cfda5332bf098b7042edccac`.
All private evidence files are mode 0600.
