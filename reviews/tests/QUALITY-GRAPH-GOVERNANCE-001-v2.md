```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"APPROVED","specSha256":"ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"d12f34af6e1aa86e9dc0397c1266ce5308dcf80f089f12c388d3ab56c59f1840"}],"redCommit":"871c91d8fc2fe1db6eefdd3ad1b5a3fb66502e35","recordedAt":"2026-09-02T23:59:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.5 — corrected RED v2

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, test, or implementation
- Reviewed RED commit: `871c91d8fc2fe1db6eefdd3ad1b5a3fb66502e35`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v2.md`
- Test seam: spec-permitted test-only checker executable with canonical `--repo` fixture root
- Verdict: `APPROVED`

## Findings

No blocking findings for this tracer slice.

The correction resolves the prior review's isolation defect. The test creates a fresh short-lived Git repository, commits a valid base, invokes the checker with that fixture as the explicit canonical repository root, and removes the complete fixture tree in `finally`. It neither reads nor mutates the production checkout's future receipt inventory. Two consecutive review runs reproduced the same intended failure and left no `fmonitor-qgg-*` fixture directories behind.

- **Traceability:** the test cites `QUALITY-GRAPH-GOVERNANCE-001 v0.5` and covers the acceptance outcome where receipt discovery finds no current inventory.
- **Seam choice:** `--repo` is explicitly permitted only for a test executable by the normative spec. The fixture remains a real Git repository, so the test does not replace Git behavior with mocks.
- **Intended RED versus setup:** PHP syntax, production-checkout discovery, temporary directory creation, Git initialization/configuration/add/commit and process creation all complete before the failing assertion. The child failure is the absent checker file, so the expected governance classification is genuinely missing.
- **Sensitivity:** a generic nonzero exit cannot satisfy the test. It requires exactly one stable `missing_receipt` protocol line for `delivery/evidence` and rejects any `DELIVERY_EVIDENCE_OK` line.
- **Expected-value independence:** exit behavior, category, receipt path and prohibition of a success marker are literal outcomes from the specification. The detail remains bounded but otherwise unconstrained because the executable contract does not prescribe exact detail text.
- **Determinism:** fixture identity is random only for isolation and is absent from expected output. Git author data is fixed, the repository has one deterministic base commit shape for the behavior under test, no production system or network is used, and cleanup also runs after assertion failure.

## Reproduced RED

Commands:

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Syntax validation passed. Both behavior runs exited `255` at the intended classification assertion. In each run the checker child exited `1` with:

```text
Could not open input file: /home/antropophag/code/fmonitor-2/tools/delivery/check-evidence.php
```

The assertion expected one `DELIVERY_EVIDENCE_FAILURE category=missing_receipt receipt=delivery/evidence ...` line and observed none. No fixture directory remained after either run.

## Reviewed hashes and lineage

```text
ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174  specs/QUALITY-GRAPH-GOVERNANCE-001.md
d12f34af6e1aa86e9dc0397c1266ce5308dcf80f089f12c388d3ab56c59f1840  tests/Verification/quality_graph_governance_001_test.php
```

The corrected RED evidence metadata agrees with the current spec hash, complete test set, test hash and base commit. The bytewise test diff from base `9c87164393b2048428fc0987c357e65e0e9fc146` through corrected RED contains exactly this added test. Git history preserves the earlier RED and `CHANGES_REQUESTED` review before commit `871c91d8fc2fe1db6eefdd3ad1b5a3fb66502e35`.

## Authorized minimal GREEN

This approval authorizes only the smallest offline checker executable needed to discover that the canonical fixture contains no receipt inventory and emit the required fail-closed `missing_receipt` terminal protocol for an absent or empty inventory, without a success marker. It does not authorize implementing the rest of the receipt validator or Quality Graph integration speculatively.

The production `make delivery-evidence-check` delegation remains required by the specification, but this corrected tracer does not exercise it; it needs its own RED before implementation.

## Required later acceptance tests

Further RED/independent-review cycles are still required for:

- an explicitly existing but empty receipt directory, plus unreadable roots, malformed/non-UTF-8 JSON, non-regular inventory entries and symlinks;
- the production Make target's exact delegation and real-checkout `HEAD`/GitHub `GITHUB_SHA` binding;
- strict receipt and canonical metadata schemas, unknown-field rejection and deterministic aggregated failure ordering;
- normalized path containment, regular-file requirements, deletion semantics and artifact hashes;
- exact authoritative metadata equality, current spec binding and independent reviewer identities;
- Git-derived complete test/implementation sets, first-exact-blob commit derivation and strict gate ancestry;
- immutable receipt supersession, unique leaf, missing target, cycle and historical-mutation detection;
- exact implementation commit, post-review envelope restrictions and changed spec/test/source invalidation;
- valid-chain success cardinality and exact `DELIVERY_EVIDENCE_OK receipts=<n> head=<sha>` output;
- Quality Graph declaration/generated drift, required nodes/results/reports, exact package/runtime pins and repository-command reuse;
- runner/publisher trust separation, full result provenance and replay rejection;
- every representative-PR parity and added-governance matrix row, including actual GitHub run IDs/URLs.

Gate 3 is approved only at the narrow scope above. Any expectation change restarts Gate 2.
