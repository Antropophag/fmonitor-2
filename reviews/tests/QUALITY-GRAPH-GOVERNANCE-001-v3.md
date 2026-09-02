```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"CHANGES_REQUESTED","specSha256":"ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"081eb9968e491d423e572c4216b0274980efbfc8147715869702681517442ea8"}],"redCommit":"10455a2f985d3c06f99e89fdc0c21cc9e553a2a8","recordedAt":"2026-09-03T00:02:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.5 — unsafe-path RED v3

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, test, or implementation
- Reviewed RED commit: `10455a2f985d3c06f99e89fdc0c21cc9e553a2a8`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v3.md`
- Test seam: isolated Git fixture through the spec-permitted checker `--repo` seam
- Verdict: `CHANGES_REQUESTED`

## Blocking finding

1. **The new unsafe-path scenario does not prove fail-closed terminal behavior.** It asserts exactly one matching `unsafe_path` line, but unlike the preceding missing-inventory scenario it does not assert that this second invocation exits nonzero and emits no `DELIVERY_EVIDENCE_OK` marker. An implementation that prints the expected failure and then prints success or exits `0` would pass this test while violating the normative seam contract.

   Return to Gate 2 and add assertions against the second invocation's own captured result for nonzero status and zero success-marker cardinality. No fixture or expected category change is otherwise required.

## Checks that passed

- **Traceability:** the scenario targets the v0.5 acceptance example for an escaping artifact path and expects the exact stable `unsafe_path` category and receipt-relative path.
- **Prior-slice preservation:** the isolated missing-inventory assertions run first and are GREEN with the existing minimal checker.
- **Intended RED:** the fixture initializes and commits successfully, receipt discovery reaches the existing placeholder validator, and the failure occurs because it returns `invalid_schema` instead of `unsafe_path`. This is missing behavior rather than setup failure.
- **Seam and isolation:** the test uses the approved test-only `--repo` seam with a fresh short-lived real Git repository. Cleanup runs in `finally`; the review run left no fixture directory.
- **Expected-value independence:** the escaping path `../outside.md`, stable category and receipt path are literals derived from the spec. They are not computed using implementation logic.
- **Path sensitivity:** the receipt otherwise supplies the complete v1 top-level/artifact shape, while the unsafe spec path is encountered before nonexistent artifact content or unrelated Git history can determine the public category.

## Reproduced RED

Commands:

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Syntax validation passed. The first missing-inventory scenario passed. The process then exited `255` at the intended new assertion after the checker child exited `1` and emitted:

```text
DELIVERY_EVIDENCE_FAILURE category=invalid_schema receipt=delivery/evidence detail=receipt validation is not implemented by this tracer slice
```

The expected `unsafe_path` line was absent. No `fmonitor-qgg-*` fixture remained.

## Reviewed hashes and lineage

```text
ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174  specs/QUALITY-GRAPH-GOVERNANCE-001.md
081eb9968e491d423e572c4216b0274980efbfc8147715869702681517442ea8  tests/Verification/quality_graph_governance_001_test.php
```

The v3 RED metadata agrees with the spec hash, complete test set, current test hash, base commit and observed failure. Commit history places this RED after the approved missing-inventory review and its minimal GREEN.

## Potential approval scope after correction

Once the two terminal assertions are added and the intended RED is recaptured, this tracer may authorize only enough strict receipt-shape traversal and normalized repository-relative path validation to classify this complete receipt's `../outside.md` as `unsafe_path` before artifact access, exit nonzero and suppress success. It does not authorize other schema/path cases, artifact verification, lineage, graph integration or CI behavior.

Absolute paths, alternate traversal forms, symlinks, non-regular files, malformed/unknown receipt fields, other artifact path positions, deletion paths and all remaining items listed in the v2 review still require later RED cycles.

Gate 3 is not approved; no additional production implementation is authorized from this revision.
