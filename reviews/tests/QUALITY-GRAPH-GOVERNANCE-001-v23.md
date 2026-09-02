```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"CHANGES_REQUESTED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"b3dcb8238d898a89a53f6e153ae9043d6db0c00955d9ded76df9dcc8ccfdd50c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"b2a130c7d38e4a73fe912655c7d5d661d78648c42b3028b9550f6115d71ae809"}],"redCommit":"736890616bd4f026601361510691be314f471131","recordedAt":"2026-09-03T05:23:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — runner bootstrap RED v23

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, or implementation
- Reviewed RED commit: `736890616bd4f026601361510691be314f471131`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v23.md`
- Verdict: `CHANGES_REQUESTED`

## Blocking finding

1. **The test converts one allowed implementation into an unapproved normative interface.** Approved v0.6 requires CLI/provider packages to be exactly `0.1.7`; it does not require the runner to bootstrap them with the literal shell command `python -m pip install quality-graph-cli==0.1.7 quality-graph-github==0.1.7`, nor does it require exactly four textual occurrences across YAML and TOML. A runner using the already pinned project/lock through `uv sync`, `uv run`, or another repository-owned deterministic mechanism can satisfy the specification but is rejected by this test. The exact command is an implementation detail rather than an observable agreed seam.

Return to Gate 1 if the owner intends to mandate pip bootstrap and exact textual occurrence counts. Otherwise rewrite Gate 2 around observable runner behavior: the generated/validated runner must invoke CLI/provider version `0.1.7` from the pinned project and reject a mutated mixed/ranged declaration, without prescribing a specific package installer command. The test header should also cite current v0.6 rather than v0.5.

## Checks that passed

- No new third-party Action or unapproved Action SHA is introduced.
- The cross-file stripping logic detects remaining `quality-graph-cli`/`quality-graph-github` names after approved exact values are removed.
- The added ranged YAML mutation is rejected, and existing runtime/TOML mutations remain sensitive.
- PHP/bootstrap and canonical declaration/project files are available; RED is the absent exact command, not setup failure.
- Evidence v23 contains the complete sorted base-to-RED test set and matching hashes.

## Reproduced RED

```text
php -l tests/Verification/quality_graph_toolchain_001_test.php
php tests/Verification/quality_graph_toolchain_001_test.php
```

Syntax passed. The test exited `255` at the occurrence-set assertion because the declaration lacks the newly demanded pip command. This is reproducible but does not prove a missing behavior from the approved spec.

## Reviewed hashes

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
b3dcb8238d898a89a53f6e153ae9043d6db0c00955d9ded76df9dcc8ccfdd50c  tests/Verification/quality_graph_governance_001_test.php
b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64  tests/Verification/quality_graph_publisher_001_test.php
b2a130c7d38e4a73fe912655c7d5d661d78648c42b3028b9550f6115d71ae809  tests/Verification/quality_graph_toolchain_001_test.php
```

Gate 3 is not approved; no exact pip bootstrap implementation is authorized from v23.
