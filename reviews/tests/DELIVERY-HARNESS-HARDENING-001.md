# Gate 3 review — DELIVERY-HARNESS-HARDENING-001

- Reviewer: independent Codex agent `/root/issue90_gate3` (`gpt-5.6-sol`, low); did not author reviewed artifacts.
- Reviewed source: `ceacb60b3fb61bbfe7f6b76e96713da4b8981524d7329789e128271b58928981`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T072330Z-08a1b59e68/package.json`.
- Snapshot patch SHA-256: `da633e1279a43ef0a95dfc19b73bf32a9b2f1204abaf7d13b293155a9bb7b4dd`.
- Verdict: `CHANGES_REQUESTED`.

## Findings

1. Retained RED includes public `SETUP_FAILURE: malformed acceptance mapping`; clarify and test the schema-bootstrap boundary, then recapture intended behavioral RED.
2. Gate 3 bootstrap input omits R5 fields; make the one-time pre-schema transition explicit and ensure the full target input is reviewed and becomes enforceable.
3. R1 matrix must compare full stdout/stderr bytes and complete timeout provenance, not only file existence.
4. Tests must not require exact normalized exits beyond the normative contract.
5. R2 must execute the returned plan through `run` and add one real runner → wrapper → CI aggregate chain.

No Gate 3 approval is inferred. A corrected exact-source package requires fresh independent rereview.

## First rereview

- Reviewed corrected source: `f98502fcb7c8822cf87d0ac8f56443715054fbf6f2e42c7a52aa1544f081c712`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T072839Z-3bdcef7f10/package.json`.
- Verdict: `CHANGES_REQUESTED`.

The bootstrap, byte assertions, exit semantics, returned-plan run and intended RED
findings were closed. Two findings remained: aggregate input was hard-coded instead
of derived from the wrapper result, and timeout raw provenance was asserted only as
non-null. A second corrected exact-source rereview is required.

## Final rereview

- Reviewed source: `d3097590ec0dd6721513a31f445d3ae6ac585c156a74fd160e4ae0810ac60951`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T073200Z-f936b18313/package.json`.
- Verdict: `APPROVED`.

The aggregate conclusion is derived from the real wrapper return code, timeout
retains exact raw `-SIGTERM` provenance beside normalized exit `124`, the snapshot
hash matches its manifest, and both intended REDs are attributable to the missing
schema/roster behavior. Gate 4 may proceed without changing approved expectations.

## Final-byte Gate 3 restart after Gate 5 finding

- Reviewed pre-implementation source: `bb1fdcf9642f96fd9eac0411e61098b9e311e3547714dabf3ea48c77ead359e7`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T074922Z-2138ddb24f/package.json`.
- Final hardening test SHA-256: `e3450ee339dde5bdaf1b4acadf21ec739e14f63044045a5cf4f9e8a88392b73a`.
- Final mutation test SHA-256: `66b519785eb91575487a31c2980afe2fad31e369663a1fee89be63f9aa797f28`.
- Verdict: `APPROVED`.

The final test bytes reproduce healthy RED for missing dimensions/roster behavior,
retain mapped GREEN regressions, cover R1–R5 through public seams, and exercise all
seven declared mutations. This approval supersedes the earlier changed-test gap.

## Final six-command mapping rereview

- Reviewed pre-implementation source: `c89469836eada102bdc8f7ec8f3f6e83e7be6edbeb002090b0fad58e595a0887`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T075336Z-c291b617a9/package.json`.
- Verdict: `APPROVED`.

The unchanged approved test bytes now map `architecture_guard_001_test.py` as an
explicit GREEN acceptance. All six argv/source/environment records match, both new
tests remain INTENDED_RED before implementation, four regressions are GREEN, and
bootstrap/full inputs carry the same expectation set.

## Owner-corrected product/agent boundary rereview

- Reviewed pre-implementation source: `845987bb80aeb57fab0dad9f23638304b91329b7ab46a5bada1e8756a8c4176f`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T091755Z-7bc92c25c7/package.json`.
- Verdict: `APPROVED`.

The final plan contains exactly six mapped harness/tooling commands and no product,
full, category or architecture command. Final tests independently prove committed
harness-vs-product routing, bounded workflow wiring, product-roster exclusion and
the legacy planner's forbidden `make test` RED. All exact-source outcomes and
snapshot hashes match; the disposable bootstrap is review-only and non-candidate.
