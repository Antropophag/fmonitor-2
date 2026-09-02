```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"APPROVED","specSha256":"ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"7669105e48278da2f3a3fd7ad16ca215a3c438b0fc441d2082c17bd04d315ec3"}],"redCommit":"15a378932d6f4f1881445a627fcd959eea1db292","recordedAt":"2026-09-03T00:05:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.5 — corrected unsafe-path RED v4

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, test, or implementation
- Reviewed RED commit: `15a378932d6f4f1881445a627fcd959eea1db292`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v4.md`
- Test seam: isolated Git fixture through the spec-permitted checker `--repo` seam
- Verdict: `APPROVED`

## Findings

No blocking findings for this tracer slice.

The correction closes the v3 sensitivity gap. The unsafe-path invocation now independently requires all three fail-closed terminal properties: nonzero status, exactly one `unsafe_path` failure for the discovered receipt, and no `DELIVERY_EVIDENCE_OK` marker. A mixed success/failure or zero-exit implementation can no longer pass.

- **Traceability:** the fixture exercises the v0.5 acceptance example for an escaping repository-relative artifact path and expects the specified stable category.
- **Intended RED:** the prior missing-inventory assertion is GREEN; fixture initialization, Git commit and receipt discovery succeed. The current checker exits nonzero without success but emits its known placeholder `invalid_schema`, so the failure is specifically the missing unsafe-path behavior.
- **Seam and isolation:** the test uses the expressly permitted test-only canonical `--repo` seam over a real short-lived Git repository. Cleanup executes in `finally` and the reproduced run left no fixture directory.
- **Sensitivity:** wrong category, missing category, duplicate category, zero exit, or any success marker independently fails the scenario.
- **Expected-value independence:** `../outside.md`, `unsafe_path`, the receipt-relative path and terminal invariants are direct literals from the specification rather than values recomputed from implementation logic.
- **Determinism:** random fixture naming affects isolation only and is not asserted. The scenario is offline and does not depend on repository receipts or production systems.

## Reproduced RED

Commands:

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Syntax validation passed. The test exited `255` at the intended unsafe-path category assertion. The child exited `1`, emitted no success marker, and reported:

```text
DELIVERY_EVIDENCE_FAILURE category=invalid_schema receipt=delivery/evidence detail=receipt validation is not implemented by this tracer slice
```

Expected cardinality for the exact `unsafe_path` line was one; actual was zero. No `fmonitor-qgg-*` fixture remained.

## Reviewed hashes and lineage

```text
ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174  specs/QUALITY-GRAPH-GOVERNANCE-001.md
7669105e48278da2f3a3fd7ad16ca215a3c438b0fc441d2082c17bd04d315ec3  tests/Verification/quality_graph_governance_001_test.php
```

The v4 evidence metadata matches the current spec hash, complete base-to-RED test set, exact test hash, base commit and observed failure. Git history retains the prior v3 `CHANGES_REQUESTED` record before this corrected RED commit.

## Authorized minimal GREEN

This approval authorizes only enough strict receipt-shape traversal and normalized repository-relative path validation to reject this otherwise structurally complete receipt's escaping `artifacts.spec.path` as `unsafe_path` before artifact access, with nonzero exit and no success marker. The earlier missing-inventory behavior must remain GREEN.

It does not authorize generalized schema completion or other path positions/forms. Absolute paths, alternate traversal, symlinks, non-regular files, malformed and unknown fields, artifact hashes, metadata/lineage, Make delegation, Quality Graph integration, publisher provenance and representative-PR parity remain subject to later RED and independent review.

Gate 3 is approved only for the narrow behavior above. Any expectation change restarts Gate 2.
