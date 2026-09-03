```delivery-metadata
{"schemaVersion":1,"kind":"code-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_gate5_review","verdict":"CHANGES_REQUESTED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"bb6ad69744fe72c8e733326719e5a0dec8d351038a2f6dc7962542fc189375d7"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"implementationCommit":"7b554a8e68aa8b2e6d982fd0d48644a90c61674e","implementationFiles":[{"path":"tools/delivery/check-evidence.php","status":"M","sha256":"84df0a1b0071b2a94064177e5e7207c6869b296ee1855aff987f06e3da312976"}],"recordedAt":"2026-09-03T21:40:11+03:00"}
```

# Code review: QUALITY-GRAPH-GOVERNANCE-001 v2

- Gate: 5 — independent code review
- Reviewer: separately tasked agent `/root/qg_gate5_review`
- Independence: reviewer did not author the specification, tests, Gate 3 record, implementation, or GREEN evidence
- Exact reviewed implementation commit: `7b554a8e68aa8b2e6d982fd0d48644a90c61674e`
- Evidence-envelope head inspected: `9c6c7e896241aa2f7a63f7bdb4549fe757f6bd3c`
- Specification: `specs/QUALITY-GRAPH-GOVERNANCE-001.md` v0.6, SHA-256 `189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859`
- Latest test review: `reviews/tests/QUALITY-GRAPH-GOVERNANCE-001-v31.md`
- GREEN evidence: `docs/operations/quality-graph-governance-green-evidence-v2.md`
- Previous review: `reviews/code/QUALITY-GRAPH-GOVERNANCE-001.md` (`CHANGES_REQUESTED`)
- Verdict: **CHANGES_REQUESTED**

## Standards

The focused metadata-binding test, publisher test, toolchain test and graph
validation are GREEN. The publisher boundary is least-privilege: it has no PR
checkout or command/issue trigger, invokes only pinned `watch`/`publish`, and
keeps `actions: read`, `contents: read`, `checks: write`. No new product state
seam or blocking Fowler smell was found.

Gate 4 nevertheless exceeds its independent authorization. Gate 3 v31 permits
only enough implementation to reject the RED metadata `sliceId` mismatch and
explicitly requires another RED/review for broader metadata behavior. The
reviewed commit also binds every artifact's `schemaVersion` and `sliceId`, RED
`specPath`/`baseCommit`, GREEN `testReviewRecordPath`, and receipt-carried review
`specSha256`. Those useful checks were not independently approved by v31, so
the minimal-GREEN rule in `docs/development-process.md` is not satisfied.

Repository integration is also not GREEN. Fresh `make architecture-check`
fails on the `PilotHttp.php` SQL-ownership fingerprint and 551-to-552 hotspot
ratchet. These failures reproduce outside the Quality Graph production delta,
so they are recorded as pre-existing integration blockers rather than defects
introduced by commit `7b554a8`.

## Specification

1. **Blocking — post-review evidence envelope is broader than the contract.**
   The approved spec permits only the code-review record, receipt chain,
   OpenSpec task status, and parity/final-verification evidence after the
   reviewed implementation. `check-evidence.php` instead permits every path
   below `docs/operations/`. Unrelated operational records can therefore change
   after GREEN without `commit_mismatch`. Replace this directory-wide exception
   with the exact approved parity/final-verification evidence set and cover it
   through RED and independent Gate 3 review.

2. **Blocking — required positive lineage and full GREEN are absent.** There is
   no committed `delivery/evidence/...` receipt, so `make
   delivery-evidence-check` cannot demonstrate the valid-chain acceptance case.
   GREEN v2 expressly does not claim repository-wide verification. Tasks 4.5
   and 7.1 require `make architecture-check`, `make verify`, graph validation,
   and lineage governance GREEN on the reviewed implementation plus evidence
   envelope; that evidence does not exist yet.

3. **Blocking — representative PR migration evidence is incomplete.** The
   required same-head positive parity, forced legacy-stage failure, graph drift,
   missing/stale lineage, stale provenance, omitted-result cases, and trusted
   publisher phase B are not all demonstrated with actual run URLs/IDs. The
   unchecked OpenSpec tasks 6.1–6.3 accurately reflect this gap.

4. **Blocking — Gate 3 v31 is a tracer approval, not whole-slice approval.** It
   does not approve the additional implementation listed above or close the
   previous review's missing executable publisher rejection proof for wrong
   repository/PR/head/run/attempt/digest and omitted node artifacts. Focused
   success cannot substitute for the spec's explicit representative-PR cases.

## Verification observed

At evidence-envelope head `9c6c7e8` with implementation blob identical to
`7b554a8`:

```text
php -l tools/delivery/check-evidence.php: PASS
php tests/Verification/quality_graph_governance_001_test.php: QUALITY-GRAPH-GOVERNANCE-001 TESTS PASSED
php tests/Verification/quality_graph_publisher_001_test.php: QUALITY-GRAPH-PUBLISHER-001 TESTS PASSED
php tests/Verification/quality_graph_toolchain_001_test.php: QUALITY-GRAPH-TOOLCHAIN-001 TESTS PASSED
make quality-graph-validate: QUALITY_GRAPH_VALIDATION_OK digest=a6d37d59715b355c8e717ad6f06a71f50f09806dbd6a57dcfcdea7a0f0a8dbdf
make architecture-check: FAIL (PilotHttp sql_ownership and hotspot_ratchet)
make verify: FAIL (architecture-check and further repository regressions)
```

GitHub Actions run `33780511678` on exact implementation SHA `7b554a8` and run
`33791635526` on envelope SHA `9c6c7e8` are both terminal `failure`: graph
validation passed, delivery evidence failed, and repository verification was
skipped. Neither exact-SHA state is a GREEN integration.

## Required corrections

1. Return all behavior beyond v31's narrow authorization, including the exact
   post-review allowlist, through a fresh honest RED and independent Gate 3.
2. Produce a valid immutable receipt and make the public lineage seam GREEN.
3. Complete the executable representative-PR/parity cases required by the spec.
4. Obtain repository-wide GREEN required by tasks 4.5/7.1, then request a fresh
   independent Gate 5 review on the exact replacement implementation commit.
