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
