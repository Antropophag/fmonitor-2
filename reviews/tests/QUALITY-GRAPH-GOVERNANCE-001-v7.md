```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"APPROVED","specSha256":"ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400"}],"redCommit":"aac8eea420ca3fb7dfb57d096d9cea771db981e8","recordedAt":"2026-09-03T00:28:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.5 — corrected hash RED v7

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, test, or implementation
- Reviewed RED commit: `aac8eea420ca3fb7dfb57d096d9cea771db981e8`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v7.md`
- Test seam: isolated committed Git fixture through the checker `--repo` seam
- Verdict: `APPROVED`

## Findings

No blocking findings for this tracer slice.

The v6 seam defect is corrected: `specs/missing.md` is added and committed successfully before the hash invocation. Its literal content is independent of the receipt's deliberately wrong 64-character lowercase digest, so the scenario now distinguishes a present committed regular artifact from both an absent artifact and matching content.

- **Traceability:** the scenario covers the v0.5 mutated-artifact `hash_mismatch` acceptance outcome.
- **Intended RED:** missing inventory, unsafe path and missing artifact remain GREEN. Git add/commit is separately asserted as setup. The next invocation reaches the remaining validator and returns placeholder `invalid_schema`, making the new hash classification the sole intended failure.
- **Seam and isolation:** a real short-lived Git repository supplies stage-committed content through the permitted test-only root. The production checkout is untouched and cleanup runs in `finally`.
- **Sensitivity:** the scenario independently requires nonzero exit, exactly one `hash_mismatch` line for the receipt, and no success marker.
- **Expected-value independence:** the committed bytes (`present but changed`) and expected receipt digest (`aaaa...`) are independently fixed literals; expected category and terminal behavior come from the specification.
- **Determinism:** Git identity and commands are fixed, random fixture naming is not asserted, execution is offline, and review execution left no fixture directory.

## Reproduced RED

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Syntax passed. Earlier assertions were GREEN. The process exited `255` at the intended hash category assertion after the child exited `1`, emitted no success marker, and reported `invalid_schema` instead of `hash_mismatch`.

## Reviewed hashes and lineage

```text
ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174  specs/QUALITY-GRAPH-GOVERNANCE-001.md
a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400  tests/Verification/quality_graph_governance_001_test.php
```

Evidence v7 matches the specification hash, complete base-to-RED test set, exact test hash, base commit and reproduced failure. Git history retains the v6 `CHANGES_REQUESTED` review before corrected RED commit `aac8eea420ca3fb7dfb57d096d9cea771db981e8`.

## Authorized minimal GREEN

This approval authorizes only enough committed regular-file SHA-256 validation to classify this present safe specification artifact's deliberately wrong receipt digest as `hash_mismatch`, exit nonzero and suppress success. All earlier scenarios must remain GREEN.

Matching-hash continuation, other artifacts/statuses, symlinks and non-regular files, metadata equality, full schema, Git-derived gate commits and sets, Make/CI/Quality Graph integration, provenance and representative-PR parity remain for later RED/review cycles.

Gate 3 is approved only at this narrow scope. Any expectation change restarts Gate 2.
