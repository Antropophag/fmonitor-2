# Independent Gate 3 review — YII2-AUTH-001 Yii runtime boundary

- Reviewer: `/root/foundation_tests`, independent of test/checker author.
- Test author: root agent.
- Artifact: `tools/architecture/tests/test_yii_runtime_boundary.py`.
- Public seam: `python3 tools/architecture/check.py --json` against isolated source trees and an empty baseline.
- RED command: `python3 -m unittest tools.architecture.tests.test_yii_runtime_boundary -v`.
- RED result: 5 tests executed, 11 intended assertion failures; legacy runtime imports and HTTP/config migration calls are not yet classified.
- Verdict: `APPROVED`.

## Findings

The test drives the public architecture CLI in a temporary repository and uses an
empty baseline, so implementation cannot pass by adding debt fingerprints. It covers
all Yii composition surfaces named by the migration design: controllers, web config,
web entrypoint and console entrypoint. Both retired dependencies are independently
checked. Migration denial covers controller/config/public web while preserving the
explicit console migration seam and read-only readiness call.

The `ReliableSession` exception is narrow and sensitive: native
`session_write_close()` remains allowed only there, while `session_start()` is still
rejected. Framework and application-module dependencies remain positive controls,
preventing a blanket Yii-directory ban. The observed 11 failures are caused by the
missing new boundary classifications; all positive fixtures already pass.

Assertions rely on structured checker categories rather than diagnostic wording or
line fingerprints. The small matrix is deterministic, network-free and independent
of repository baseline state. It directly enforces ADR0003 ownership: Yii owns the
new composition and cannot restore LocalAuth/PilotHttp runtime composition or run
schema migration from ordinary HTTP/config paths.

## Required changes

None. Implement the checker classifications without changing `baseline.json`, then
rerun this test and the full architecture test suite. This approval does not review
the current uncommitted Yii authentication implementation.
