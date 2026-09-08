# Test review: original application reference — cloned issuer identity

- Reviewer: `/root/original_gate3`, independent agent; not author of test or implementation.
- Test author: root implementation agent.
- Reviewed source: frozen `18916aef904e37ddfbcf8afd9adb6abcdf642654` plus external test draft only.
- Specification: unchanged ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001 v0.1, issuing-reader-instance invariant; Gate 1 reused.
- Public seam: real native selection/original commands, public reference factory/readCurrent/confirmCurrent and ordinary PHP object cloning.
- Verdict: `APPROVED` for the exact draft below and its byte-identical adoption after the frozen full run ends.

## Findings

Read the external test and supplemental independent Gate 5 finding. Expected own-reference matched and cross-instance unavailable results follow the explicit specification, regardless of equal metadata or connection. Ordinary clone produces a distinct object instance; the test asserts that distinction and requires each reader to remain able to issue and confirm its own reference. It does not prescribe storage-map internals, use reflection or call private methods.

The matrix covers cloning before and after the original reference is issued, at prefixes 0 and 25. Both directions of cross-issuer use are exercised after both own-reference confirmations. Thus both transfer of an existing issuance and continued shared issuance after cloning are sensitive. Exact metadata equality prevents unrelated source drift from supplying the rejection. Returning unavailable for every guard would fail both positive controls.

Each case performs healthy public selection/accepted-original setup and uses task-owned native resources. Guard calls run in a caller-owned transaction containing a sentinel insert. Active transaction/write visibility and the subsequent full snapshot equality after caller rollback detect premature rollback/commit; private-file hashes remain exact. Finally rollback and fixture cleanup remain bounded by the existing native fixture. No native function, SQL result, driver or production class is intercepted.

The draft lives outside the repository to preserve the full verification source. Its app and bootstrap links target the unchanged repository files. This preserves the actual production seam while avoiding test discovery changes during the running full suite.

## RED and artifact identity

External draft:
`/Users/antropophag/.local/state/fmonitor2-verification/original-reference-20260907/clone-overlay/tests/AssignmentOrderComposition/original_application_reference_issuer_001_test.php`

SHA-256: `df73f74e28687686f118fe214212c5734c756b4a0ff71370fb83b6647bf2721f`.

Root ran that file with native PHP. Inspected `/Users/antropophag/.local/state/fmonitor2-verification/original-reference-20260907/clone-red.log`: four healthy setup/cleanup cases, each reaches the exact final assertion with actual matched/matched/matched/matched instead of matched/matched/unavailable/unavailable, exit 1. Sentinel and preservation assertions precede the intended failure and pass. This is a demonstrated issuer-identity defect, not a fixture failure. Reviewer inspected evidence and hash rather than claiming another execution.

No blocking test findings. After the frozen full run is terminal, adoption at `tests/AssignmentOrderComposition/original_application_reference_issuer_001_test.php` is covered only if byte-identical to this hash; any semantic amendment needs renewed review. Minimal repair, native focused/regression GREEN and independent Gate 5 remain required. No production or repository test was edited by the reviewer; only this record was added.
