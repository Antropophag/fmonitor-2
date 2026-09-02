```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"CHANGES_REQUESTED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"86e731bde8ae0f998229e50f936c51f65605e200061507a3b9d11385900f5bee"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"34582076cd087cbf4fe5ee32dad6e3f8809baacb","recordedAt":"2026-09-03T07:03:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — strict metadata RED v28

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, or implementation
- Reviewed RED commit: `34582076cd087cbf4fe5ee32dad6e3f8809baacb`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v28.md`
- Verdict: `CHANGES_REQUESTED`

## Blocking findings

1. **Critical Git setup statuses are ignored.** Clone status is asserted, but both `git config` calls, `git add .`, and the mutation `git commit` discard their results. The intended scenario depends on the mutated RED blob and updated receipt hash being committed. If identity configuration, staging, or commit fails, the checker can legitimately return `gate_order` for an uncommitted blob and the test reports the same apparent RED for a setup defect.

2. **“Before traversal” is not made sensitive.** The test requires one matching `invalid_schema` line but does not require exactly one total failure. A checker that emits `invalid_schema` and then continues into Git traversal and emits `gate_order` still passes. Add a total failure-marker cardinality assertion of one.

Return to Gate 2 by asserting zero status for every Git setup/mutation command (and preferably checking metadata regex/JSON/file-write preconditions explicitly), then require exactly one total failure line, the one `invalid_schema`, with no success. Recapture RED.

## Checks that passed

- The clone starts from the proven valid v1 lineage before later main-fixture mutations.
- The unknown field is added to authoritative RED metadata, while the receipt RED SHA-256 is independently updated, avoiding a trivial `hash_mismatch`.
- The expected receipt is the current `lineage-v1` in the cloned history.
- The scenario is isolated, offline and removed in nested `finally` cleanup.
- Evidence v28 contains the complete sorted base-to-RED test set and matching hashes.

## Reproduced RED

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Syntax and the preceding valid lineage passed. The checker exited nonzero with `gate_order ... docs/red.md has no unique first matching blob commit`; expected `invalid_schema` was absent. This is consistent with the intended missing validation, but the ignored commit status means the test does not independently exclude setup failure.

## Reviewed hashes

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
86e731bde8ae0f998229e50f936c51f65605e200061507a3b9d11385900f5bee  tests/Verification/quality_graph_governance_001_test.php
391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600  tests/Verification/quality_graph_publisher_001_test.php
ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863  tests/Verification/quality_graph_toolchain_001_test.php
```

After correction, approval may authorize only strict unknown-field rejection in RED metadata before Git lineage traversal. Other metadata kinds/schema violations remain for later RED cycles.

Gate 3 is not approved; no strict metadata implementation is authorized from v28.
