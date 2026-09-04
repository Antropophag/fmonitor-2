# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — private maintenance RED correction v4

- Prior Gate 3: `28fb70b35b7906979e370b4515851475169814cc` (`CHANGES_REQUESTED`)
- Result: **INTENDED RED preserved**

The shared journal is reset after fixture construction and immediately before
maintenance. The referenced-content oracle now expects the complete public
sequence `lock attempt → DIGEST_LOCK_ACQUIRED → reference lookup → unlock`,
with no delete. The active-lease oracle remains exactly one failed lock attempt.

PHP lint and diff hygiene pass; production is untouched and the current empty
orphan page remains the first honest RED.
