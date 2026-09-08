# Test rereview: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 dynamic ports v2

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit authored by Timofey Grishin
- Reviewed commit: `621e7e83efdb0073086aba5f87ab955b2b1aaba7`
- Specification: active `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v61, SHA256 `d65470e2e1da510aa1ebe6d9cce6549b8fffcc6bf66035716623eb0cad61f890`
- Public seam: unchanged `AssignmentOrderOriginalApplication::submitAssignmentOrderOriginal(Command): Result`
- Red command and intended failure: `php tests/InstallationProcess/assignment_order_original_dynamic_ports_001_test.php`; unchanged 24 intended failures and 9 positive controls, aggregate exit `255`
- Verdict: `APPROVED`

## Exact reviewed inputs

```text
d65470e2e1da510aa1ebe6d9cce6549b8fffcc6bf66035716623eb0cad61f890  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
4a83de1d2770ccd8f5c70604a3f678d9e9f24298317f4291e6dcfad8f757df86  tests/InstallationProcess/assignment_order_original_dynamic_ports_001_test.php
c04f1e73636869adde1eff2d6d38a2af11900f16534a3291c48daa9faa65e4aa  tests/Support/AssignmentOrderOriginalDynamicPortsFixture.php
9c0fcfc4211ab836881cab895b6b0c70a59d3a9e36f6f1712bef78949bc2ff63  docs/operations/original-command-dynamic-ports-red-v2-2026-09-06.md
0491dd1ed66488c3c154161590bcb8a9d79179604aeeb49de5604706fcf2dcd3  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-dynamic-ports-v1.md
300d90d4a749257b16dc45b7667e75ad94a17550f0d5d2eda7b03b64c00fa25a  /Users/antropophag/.local/state/fmonitor2-verification/original-dynamic-red-h47kdud9/red-v2.log
```

The v1 review remains immutable history. This rereview covers one corrected assertion only; the parent specification, fixture, production source and every other test expectation are unchanged.

## Finding disposition

The v1 post-stream case overconstrained the complete lifecycle array to empty. The active parent transcript requires the earlier `AFTER_REQUEST_MISS_BEFORE_STREAM` event before stage creation and stream reading. That event may therefore already be present when the subsequent accepted-fingerprint lookup returns `UNAVAILABLE`; forbidding it contradicted the approved lifecycle contract.

The amended assertion filters the observed lifecycle only for these three exact later enum events:

- `AFTER_FINGERPRINT_MISS_BEFORE_CAS`;
- `AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT`;
- `AFTER_COMMIT_BEFORE_RETURN`.

It requires that filtered list to be empty. This preserves the intended sensitivity: unavailable fingerprint lookup cannot be treated as a miss, cannot advance to CAS, cannot finalize private content, and cannot reach an accepted commit/return boundary. The existing assertions still require the exact real fingerprint, zero ID allocations, two stream reads, one begun stage, zero finalize, one abort, one stage close, no lease, one stream close, unchanged facts/audits and zero delivery.

The correction permits only earlier or otherwise independently governed lifecycle events. It does not assert lifecycle completeness, add a new event, weaken the post-stream failure result, or change the public seam. Complete lifecycle coverage remains the separately assigned audit and is not inferred here.

Independent execution reproduces the same 33-case distribution and aggregate exit `255`: 24 intended failures and 9 controls. The new raw log is byte-identical to the prior authoritative RED log and has SHA256 `300d90d4a749257b16dc45b7667e75ad94a17550f0d5d2eda7b03b64c00fa25a`. PHP lint and `git diff --check` pass.

No blocking traceability, expected-value independence, sensitivity, seam-choice or determinism finding remains for the amended assertion. The v1 assessment of all other dynamic-port cases remains applicable to their unchanged bytes.

## Required changes

None.

This approval is limited to the corrected dynamic-port test. It does not establish GREEN, lifecycle completeness, Gate 5, combined original-command approval, or release readiness.
