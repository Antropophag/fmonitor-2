```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"CHANGES_REQUESTED","specSha256":"ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"da5d51fd815015887334d27cc5504d76df8bf4fbb1b2dd70e2011a59cf4712de"}],"redCommit":"c839ef7bd8d64109a7f80b84aed5b7252bb24c2b","recordedAt":"2026-09-03T00:43:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.5 — exact-toolchain RED v8

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, test, or implementation
- Reviewed RED commit: `c839ef7bd8d64109a7f80b84aed5b7252bb24c2b`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v8.md`
- Seam: repository-owned `quality-graph.yml` and `pyproject.toml`
- Verdict: `CHANGES_REQUESTED`

## Blocking findings

1. **Floating runtime rejection is not fail-closed.** The negative regex rejects only refs beginning `main`, `v<digit>` or `0.1`. A declaration containing the required exact SHA once plus another Quality Graph use at `master`, `latest`, another branch name, a tag with another spelling, or a short SHA would pass. The specification requires every floating or mixed pin to fail.

2. **Mixed Python dependency pins are not rejected.** The test requires one exact line for each package, but it does not reject an additional `quality-graph-cli>=0.1` or second provider declaration elsewhere in the TOML. Thus a mixed exact/floating project can satisfy both assertions despite the normative “floating or mixed pins fail” rule.

Return to Gate 2 with assertions that enumerate all occurrences of each governed runtime/package and prove the occurrence set is exactly the single approved value. Prefer parsing the relevant YAML/TOML structure or otherwise matching every governed reference rather than maintaining a blacklist of known floating spellings. Include mutation fixtures (or an equivalent isolated validator seam) showing an extra non-exact runtime and dependency are rejected; inspecting only the intended final files cannot prove the rejection behavior.

## Checks that passed

- The exact runtime commit and both `0.1.7` package values are independently fixed literals from the approved specification.
- The test uses repository-owned declaration/configuration files rather than network or installed package state.
- The evidence metadata contains the complete bytewise-sorted base-to-RED test set and matching hashes.
- PHP syntax passes and RED occurs at the first missing product artifact, not bootstrap/setup.

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

This establishes the missing declaration, but later assertions are not yet sensitive enough to authorize fail-closed pin validation.

## Reviewed hashes

```text
ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174  specs/QUALITY-GRAPH-GOVERNANCE-001.md
a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400  tests/Verification/quality_graph_governance_001_test.php
da5d51fd815015887334d27cc5504d76df8bf4fbb1b2dd70e2011a59cf4712de  tests/Verification/quality_graph_toolchain_001_test.php
```

## Potential approval scope after correction

A corrected tracer may authorize only the canonical declaration/project structure and exact single runtime/CLI/provider pins, plus rejection of any additional mixed or floating occurrence. Graph semantics, generated drift, execution, provenance, publisher security and CI wiring still require later RED and independent reviews.

Gate 3 is not approved; no toolchain implementation is authorized from v8.
