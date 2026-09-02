# Code review: QUALITY-GRAPH-GOVERNANCE-001

- Reviewer: `agent:/root/qg_code_review`
- Implementation author: `agent:/root`
- Reviewed commit: `3678f067d748c7de4d91d84b6c7faa16faa05cf9`
- Specification: `specs/QUALITY-GRAPH-GOVERNANCE-001.md` v0.6, SHA-256 `189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859`
- Approved test review: not available for the final test set; `reviews/tests/QUALITY-GRAPH-GOVERNANCE-001-v23.md` is `CHANGES_REQUESTED`
- Verification commands: focused three-test run; `make quality-graph-validate`; `make delivery-evidence-check`; repository-wide result from the committed baseline record
- Verdict: `CHANGES_REQUESTED`

No `delivery-metadata` block is emitted because a canonical Gate 5 record is not constructible: there is no approved Gate 3 review for the final tests, no GREEN evidence artifact, and no receipt from which to derive the required implementation set. Inventing those fields in this review would defeat the lineage contract being reviewed.

## Standards

The repository-owned seams and the reduced publisher are small and readable. The publisher has the intended read/read/write permissions, no checkout, no `issue_comment`, no command job, and invokes only the pinned runtime's `watch`/`publish` operation. Generated runner jobs retain read-only checkout with credentials disabled. No baseline Fowler smell is independently blocking.

The delivery process itself is not satisfied. Gate 4 proceeded after the latest changed toolchain test received `CHANGES_REQUESTED`, and the reviewed commit has neither final GREEN evidence nor a receipt. This violates the mandatory RED → independent approved test review → GREEN order in `docs/development-process.md`. The repository-wide `make verify` result is also RED. Its application/architecture failures are credibly documented as pre-existing and are not implementation defects in this diff, but the process does not permit a final Gate 5 approval while full verification is nonzero.

## Spec findings

1. **Blocking — architecture validation does not enforce the approved pins.** `tools/delivery/check-quality-graph.php` checks generated drift and the publisher transformation, but never validates the runtime SHA, package versions in `pyproject.toml`/lock data, or Action pins. Regenerating declaration, manifest and workflows together with a floating runtime, mixed package versions, or different checkout/upload SHAs can pass this production checker. The assertions in `quality_graph_toolchain_001_test.php` exercise a test-local `qgtPinsValid` function, not the repository checker. This fails §7.8 and task 4.4's requirement that floating or mixed pins fail architecture validation.

2. **Blocking — receipt metadata is not authoritatively bound.** `check-evidence.php` parses metadata but does not require its `schemaVersion` or `sliceId` to equal the receipt; it also ignores RED `specPath`/`baseCommit`, GREEN `testReviewRecordPath`, and the receipt copies of `testReview.specSha256` and `codeReview.specSha256`. Consequently a receipt can claim one slice/change or copied spec hashes while pointing at records whose authoritative metadata says something else and still pass if the few inspected fields line up. This violates §7.4's exact equality and the requirement that metadata be checked against authoritative evidence.

3. **Blocking — post-review implementation drift is accepted.** The checker proves ancestry only through the commit that first adds the code-review artifact. It does not inspect `codeReviewCommit..HEAD` and therefore does not reject later source, test, spec, graph, workflow, or checker changes. The spec explicitly permits only an evidence-envelope commit after Gate 5 and requires `commit_mismatch` for governed changes after review. A later implementation commit can currently reuse an old approval.

4. **Blocking — the required delivery chain is absent at the reviewed commit.** `delivery/evidence/` does not exist, so the public seam correctly exits nonzero with `missing_receipt`. There is also no GREEN operations record. Thus the `delivery-evidence` node necessarily fails for every PR head represented by this commit; this is fail-closed behavior, but not a completed implementation or a valid positive case.

5. **Blocking — representative migration evidence is partial.** PR #1 proves runner provenance and the missing-evidence negative case only. The same-head positive parity case, forced repository-stage failure comparison, graph drift, stale lineage, stale/mismatched result rejection, expected-artifact omission, and trusted publisher phase B are not demonstrated. The record correctly labels the bootstrap blocker and does not claim parity; tasks 6.1–6.3 and the spec's migration precondition therefore remain open.

6. **Test coverage gap — publisher rejection is asserted but not exercised.** The publisher test checks an exact YAML string and one override-drift mutation. It does not execute or fixture-test rejection of a Result v0 with the wrong repository/PR/head/run/attempt/digest or a missing node artifact. Those are explicit acceptance examples and representative-PR cases. Upstream runtime behavior may supply the mechanism, but this repository currently has no captured executable proof for its pinned version.

## Verification observed

```text
QUALITY-GRAPH-GOVERNANCE-001 TESTS PASSED
QUALITY-GRAPH-PUBLISHER-001 TESTS PASSED
QUALITY-GRAPH-TOOLCHAIN-001 TESTS PASSED
QUALITY_GRAPH_VALIDATION_OK digest=d19c74290776c2f4a5cf63480a3a6719d2b10c096bc991cdea488d14afc76401
DELIVERY_EVIDENCE_FAILURE category=missing_receipt receipt=delivery/evidence detail=receipt directory is absent
```

The committed repository-wide baseline reports `FULL_VERIFICATION_FAILURE count=4 stages=architecture-check,unit-test,db-test,e2e-test`. The application failures are pre-existing relative to `main`; absence of final GREEN, approved final tests, receipt, and parity evidence is specific to this change.

## Required changes

1. Return the final changed tests to Gate 3 and obtain an independent `APPROVED` review before any replacement GREEN checkpoint.
2. Put pin enforcement in the production architecture checker and add mutations that invoke that public seam.
3. Enforce complete receipt-to-metadata equality, including slice/schema/spec/base/review-path fields and receipt-carried spec hashes.
4. Reject governed changes after the reviewed implementation commit, allowing only the explicitly enumerated evidence envelope.
5. Capture GREEN evidence and a valid receipt, then make `make delivery-evidence-check` pass for the exact reviewed implementation plus envelope.
6. Complete the feasible representative PR matrix and publisher rejection proof; keep phase B and cutover explicitly blocked until base-branch topology exists.
7. Resolve the separately governed pre-existing repository regressions and rerun `make architecture-check` and `make verify` before requesting final Gate 5 review.
