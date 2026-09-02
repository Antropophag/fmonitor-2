```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"APPROVED","specSha256":"ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"03b96f302a6b685f02916d6620180e1bd426578b","recordedAt":"2026-09-03T01:03:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.5 — corrected toolchain RED v9

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, or implementation
- Reviewed RED commit: `03b96f302a6b685f02916d6620180e1bd426578b`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v9.md`
- Seam: repository-owned `quality-graph.yml` and `pyproject.toml`
- Verdict: `APPROVED`

## Findings

No blocking findings for this tracer slice.

The corrected test gathers every governed Quality Graph runtime occurrence and both governed Python package occurrences, then exact-compares those complete sets with the single approved runtime SHA and the two exact `0.1.7` package pins. It no longer depends on a blacklist of familiar floating spellings.

- **Traceability:** literals match requirement 8 of v0.5: runtime `caf5366a04ca01b230f1df5585d0fbd9693d7bef`, CLI `0.1.7`, provider `0.1.7`.
- **Intended RED:** PHP/bootstrap succeed and the test fails at the first missing artifact, `quality-graph.yml`; no package installation or network access is needed.
- **Sensitivity:** mutation probes independently reject an added `@master`, an added ranged CLI dependency, and replacement of the provider with `0.1.6`. Because occurrence sets are exact, arbitrary additional runtime/package refs also fail rather than relying on those three spellings alone.
- **Expected-value independence:** the allowlist values come directly from the approved executable specification and audited release, not implementation output.
- **Determinism:** only repository bytes are inspected; ordering is normalized for package occurrences, runtime source order is required to be the sole exact occurrence, and no external state participates.

## Reproduced RED

```text
php -l tests/Verification/quality_graph_toolchain_001_test.php
php tests/Verification/quality_graph_toolchain_001_test.php
```

Syntax passed. The behavior test exited `255` at:

```text
RED_ASSERTION: canonical Quality Graph declaration must exist
Expected: true
Actual: false
```

This is the first missing behavior, not broken setup.

## Reviewed hashes and test set

```text
ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174  specs/QUALITY-GRAPH-GOVERNANCE-001.md
a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400  tests/Verification/quality_graph_governance_001_test.php
ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863  tests/Verification/quality_graph_toolchain_001_test.php
```

Evidence v9 matches the current spec, exact hashes, RED commit and complete bytewise-sorted base-to-RED test set. Git history retains v8 `CHANGES_REQUESTED` before the corrected RED.

## Authorized minimal GREEN

This approval authorizes only creation of the canonical declaration and Python project containing exactly one approved immutable runtime reference and exactly one exact pin for each governed CLI/provider package, with no additional mixed/floating occurrences. It does not authorize graph node semantics, generated artifacts, command execution, drift detection, workflow/publisher security, provenance or representative-PR parity.

Gate 3 is approved only at this narrow scope. Any expectation change restarts Gate 2.
