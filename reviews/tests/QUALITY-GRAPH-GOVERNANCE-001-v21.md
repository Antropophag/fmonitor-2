```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"APPROVED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"b3dcb8238d898a89a53f6e153ae9043d6db0c00955d9ded76df9dcc8ccfdd50c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"561471987149698dfbedfe65f8654e824262a9ef","recordedAt":"2026-09-03T04:43:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — corrected supersession RED v21

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, or implementation
- Reviewed RED commit: `561471987149698dfbedfe65f8654e824262a9ef`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v21.md`
- Verdict: `APPROVED`

## Findings

No blocking findings for this tracer slice.

The supersession result now repeats the complete valid-output contract: zero exit, exactly one `receipts=1` success bound to the independently derived supersession HEAD, zero failure markers, and that exact success as terminal nonempty stdout.

- Committed v1 remains immutable.
- Uncommitted foreign duplicate is removed before the next commit.
- v2 is committed later in the same slice directory with `supersedes: lineage-v1`.
- v1 has one successor and v2 none, independently establishing one current leaf.
- The earlier valid-chain and duplicate-claimant scenarios remain GREEN.
- The fixture is offline, deterministic apart from unobserved temporary naming, and removed in `finally`.

## Reproduced RED

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Syntax and all preceding scenarios passed. The current checker rejected the legitimate v2 as `duplicate_slice`, exited `1`, and the test exited `255` at the expected-zero assertion. This is the intended missing-supersession behavior.

## Reviewed hashes

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
b3dcb8238d898a89a53f6e153ae9043d6db0c00955d9ded76df9dcc8ccfdd50c  tests/Verification/quality_graph_governance_001_test.php
b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64  tests/Verification/quality_graph_publisher_001_test.php
ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863  tests/Verification/quality_graph_toolchain_001_test.php
```

Evidence v21 matches the approved spec, complete sorted base-to-RED test set, exact hashes and reproduced outcome. This approval supersedes the v20 requested changes while preserving that record.

## Authorized minimal GREEN

This approval authorizes only acceptance of this immutable, same-directory v1→v2 chain and counting its unique current leaf, while preserving foreign duplicate rejection and the exact terminal protocol. Missing targets, multiple leaves, cycles, reused IDs and historical mutation remain for later RED cycles.

Gate 3 is approved only at this narrow scope. Any expectation change restarts Gate 2.
