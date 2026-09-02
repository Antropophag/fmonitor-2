```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"CHANGES_REQUESTED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"3957207fe6693564042cd99b47cbc441bab8964cef379ec4649646adc9fcc262"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"b196480c1d6cccb9705029daebeb67a542f56920","recordedAt":"2026-09-03T03:18:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — valid-lineage RED v16

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, or implementation
- Reviewed RED commit: `b196480c1d6cccb9705029daebeb67a542f56920`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v16.md`
- Verdict: `CHANGES_REQUESTED`

## Blocking finding

1. **The valid-lineage assertion does not prove the required terminal success protocol.** It requires exit `0` and exactly one matching `DELIVERY_EVIDENCE_OK receipts=1 head=<fixture-head>` line anywhere in combined output, but it does not forbid `DELIVERY_EVIDENCE_FAILURE` lines and does not prove the OK line is terminal. A checker that emits a failure, then the matching OK, then arbitrary trailing output and exits zero would pass despite violating the fail-closed seam contract.

   Return to Gate 2 by asserting zero failure-marker cardinality for the valid invocation and that the exact OK line is the last non-empty output line (or otherwise asserting the exact permitted output transcript). Recapture RED; fixture construction need not change.

## Checks that passed

- **Git chronology:** the fixture establishes six distinct commits in strict order: base, RED/spec/test, test review, GREEN/implementation, code review, receipt. The receipt head is independently obtained with `git rev-parse HEAD`.
- **First-blob derivation:** RED, test review, GREEN and code review metadata blobs are each created once at their corresponding commit and remain unchanged through receipt HEAD.
- **Complete test set:** base-to-RED adds exactly `tests/lineage_test.php`; its `A` status and independently hashed bytes are repeated consistently.
- **Complete implementation set:** test-review-to-GREEN adds the implementation path and GREEN evidence; after the contract's GREEN-evidence exclusion, the recorded set is exactly the implementation file with independently computed hash.
- **Metadata/receipt equality:** spec, evidence and review hashes/identities/verdicts agree with the receipt. The code review names the exact derived GREEN/implementation commit.
- **Independence:** test reviewer differs from test author; code reviewer differs from implementation author. Canonical identities are valid.
- **Isolation:** all history is a real short-lived Git repository, uses fixed local identity, requires no network/production system and is removed in `finally`.
- **Expected head independence:** the expected head comes directly from Git after the receipt commit, not from checker output.

## RED status

The v16 evidence records that earlier negative cases are GREEN and this complete lineage reaches the intentionally incomplete validator, which returns `invalid_schema`. The fixture represents the intended valid chain; only the success-protocol sensitivity above blocks approval.

## Reviewed hashes

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
3957207fe6693564042cd99b47cbc441bab8964cef379ec4649646adc9fcc262  tests/Verification/quality_graph_governance_001_test.php
b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64  tests/Verification/quality_graph_publisher_001_test.php
ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863  tests/Verification/quality_graph_toolchain_001_test.php
```

Evidence v16 contains the complete sorted base-to-RED test set and exact current hashes.

After correction, approval may authorize only the remaining strict parsing/metadata/hash/Git-lineage behavior needed for this complete valid chain to produce its exact terminal success. Invalid metadata, history, independence, set and ordering mutations still need dedicated negative RED cycles before claiming full fail-closed coverage.

Gate 3 is not approved; no full valid-lineage implementation is authorized from v16.
