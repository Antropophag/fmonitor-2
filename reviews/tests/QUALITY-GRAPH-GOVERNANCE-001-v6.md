```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"CHANGES_REQUESTED","specSha256":"ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e266b326ff9a1d423ebc35490f5f0c7aac94aaa576633ec5b58631661872a087"}],"redCommit":"b6be8f5bdab4b98ad79c4cef5e5d18f03a3c8b71","recordedAt":"2026-09-03T00:18:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.5 — hash-mismatch RED v6

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, test, or implementation
- Reviewed RED commit: `b6be8f5bdab4b98ad79c4cef5e5d18f03a3c8b71`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v6.md`
- Test seam: isolated Git fixture through the test-only checker `--repo` seam
- Verdict: `CHANGES_REQUESTED`

## Blocking finding

1. **The allegedly present specification does not exist in any fixture commit.** The fixture makes its only commit before creating `specs/missing.md`; the new file remains untracked. The normative receipt contract requires an `A`/`M` artifact hash to match stage-commit content, not arbitrary working-tree bytes. A correct Git-backed checker therefore cannot treat this file as the present committed spec whose digest mismatches. The current test would instead authorize an implementation-coupled and unsafe working-tree hash check.

   Return to Gate 2 by adding and committing the safe spec artifact in the fixture before the hash-mismatch invocation, and use the fixture's resulting commit identity wherever this narrow receipt needs a commit reference. Keep the receipt digest deliberately different from the committed blob. Recapture RED after proving the earlier missing-artifact invocation occurs before that commit and remains GREEN.

## Checks that passed

- The prior missing-inventory, unsafe-path and missing-artifact scenarios are GREEN before the new assertion.
- The new scenario requires nonzero status, exactly one `hash_mismatch` line for the receipt and no success marker.
- The expected category and receipt path are literal specification outcomes, not derived from implementation code.
- The test remains offline and isolated, and `finally` cleanup removed the fixture after the reproduced failure.
- Evidence metadata, spec hash, exact test hash and RED commit agree.

## Reproduced RED

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Syntax passed. Earlier cases were GREEN. The process exited `255` at the new category assertion after the child exited `1`, emitted no success and returned placeholder `invalid_schema` rather than `hash_mismatch`. This is reproducible, but the fixture does not yet represent the stage-commit behavior claimed by the test.

## Reviewed hashes

```text
ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174  specs/QUALITY-GRAPH-GOVERNANCE-001.md
e266b326ff9a1d423ebc35490f5f0c7aac94aaa576633ec5b58631661872a087  tests/Verification/quality_graph_governance_001_test.php
```

## Potential approval scope after correction

Once the artifact is committed and RED recaptured, approval may authorize only enough stage-commit regular-file hashing to classify this safe present spec artifact's deliberately wrong lowercase SHA-256 as `hash_mismatch`, preserving all earlier scenarios. Matching hashes, other artifact positions, deletions, symlinks, metadata, history and graph/CI behavior still require later RED cycles.

Gate 3 is not approved; no additional production implementation is authorized from v6.
