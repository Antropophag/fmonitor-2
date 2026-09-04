# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — private maintenance RED correction v3

- Prior Gate 3: `1a0fc51d923978001f3aca3e15a800e1ca157143` (`CHANGES_REQUESTED`)
- Result: **INTENDED RED preserved**

A public storage decorator and reference-port decorator now write into one
ordered trace. The verifier requires exact `lock → reference → unlock` with no
delete for a referenced finalized candidate. An actively leased candidate must
show only the failed lock attempt, with no reference, delete, or maintenance
unlock. Replay still performs no reference work.

PHP lint and `git diff --check` pass. Execution remains the honest empty orphan
page RED (`expected 1`, `actual 0`, exit `255`). Production is untouched.
