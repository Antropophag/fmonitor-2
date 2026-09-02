```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"CHANGES_REQUESTED","specSha256":"ab4292dbc393185a7fdb7c56e69607a48b1e12cc40eb1b960ef159a887d53390","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"04b942eafdfb83112c9c66b6d13000e46687adc021110c7eec34a037b8d3ab84"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"0cf7365771c2f668a3a53399a6dd81e62d12e61e","recordedAt":"2026-09-03T01:28:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — publisher RED v10

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, generated input, or implementation
- Reviewed RED commit: `0cf7365771c2f668a3a53399a6dd81e62d12e61e`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v10.md`
- Seam: repository-owned publisher workflow and retained pinned generated baseline
- Verdict: `CHANGES_REQUESTED`

## Blocking findings

1. **Gate order is not established for v0.6.** The executable specification itself says the repository-owned publisher amendment is owner-approved but its independent Gate 1 amendment review is pending. Gate 2/3 cannot authorize implementation of an amended contract before that review approves the exact v0.6 hash.

2. **The promised allowlisted baseline comparison is not tested.** The test checks only that `.quality-graph/generated-publisher-v0.1.7.yml` exists; it neither pins its bytes/hash nor compares the deployed workflow against it. An arbitrary baseline and an independently authored workflow can pass, so the required “only documented privilege-removal transformation; every other drift fails closed” behavior is unobservable.

3. **The trigger and permission ceiling are blacklist-based and incomplete.** Exactly one `workflow_run` does not exclude an additional `push`, `pull_request_target`, `schedule`, `workflow_dispatch`, or other trigger. Likewise, requiring the three allowed permission lines and banning only three explicit writes still permits `contents: write`, `id-token: write`, `deployments: write`, `packages: write`, `statuses: write`, and other extra grants. This violates “only workflow_run” and “only actions: read, contents: read, checks: write.”

4. **Runtime/job sensitivity remains open.** The runtime regex has no token boundary and does not enumerate all Quality Graph occurrences, so the exact SHA prefix plus a suffix or an additional invocation can pass. The forbidden checkout check catches only the literal `actions/checkout@`; it does not prove the publisher has the exact single generated publish job/step shape or that the retained allowlisted transformation introduced no execution of PR-controlled code.

Return first to Gate 1 for independent approval. Then make the publisher test compare parsed complete trigger, permission, job/step and runtime occurrence sets, and validate a deterministic transformation/diff from a pinned generated baseline. Add mutations for an extra trigger, an unlisted write permission, an extra runtime/job/step and non-allowlisted baseline drift.

## Checks that passed

- PHP syntax and bootstrap pass; the generated publisher input exists.
- RED occurs at the intended absent retained-baseline assertion, not at publisher setup.
- Literal expected runtime and watch/publish projection match v0.6.
- Evidence metadata contains the complete sorted base-to-RED test set and exact current hashes.

## Reproduced RED

```text
php -l tests/Verification/quality_graph_publisher_001_test.php
php tests/Verification/quality_graph_publisher_001_test.php
```

Syntax passed. The behavior test exited `255` at:

```text
RED_ASSERTION: pinned generated publisher baseline must be retained for allowlisted comparison
Expected: true
Actual: false
```

This distinguishes the available generated publisher input from the missing retained comparison artifact, but the later assertions do not yet prove the full v0.6 security boundary.

## Reviewed hashes

```text
ab4292dbc393185a7fdb7c56e69607a48b1e12cc40eb1b960ef159a887d53390  specs/QUALITY-GRAPH-GOVERNANCE-001.md
a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400  tests/Verification/quality_graph_governance_001_test.php
04b942eafdfb83112c9c66b6d13000e46687adc021110c7eec34a037b8d3ab84  tests/Verification/quality_graph_publisher_001_test.php
ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863  tests/Verification/quality_graph_toolchain_001_test.php
```

Gate 3 is not approved. No publisher implementation or baseline override is authorized from v10.
