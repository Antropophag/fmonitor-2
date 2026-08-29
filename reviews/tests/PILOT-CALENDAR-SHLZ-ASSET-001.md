# Test review: PILOT-CALENDAR-SHLZ-ASSET-001 (third review)

- Reviewer: separately tasked agent `/root/calendar_asset_test_review` (independent; did not author the specification or test)
- Test author: separately tasked Gate 2 agent
- Reviewed commit: working tree at HEAD `db8e4d888abce28daa82d75135f6d6c5f5d63874`
- Specification: [`specs/PILOT-CALENDAR-SHLZ-ASSET-001.md`](../../specs/PILOT-CALENDAR-SHLZ-ASSET-001.md), `APPROVED 2026-08-30`
- Public seam: the actual Dockerfile `shlz-ui-build` artifact image, its verified source checkout, and the declared copy of that artifact tree into `/workspace/shlz-ui` in the pilot runtime stage
- Red command and intended failure: `php tests/InstallationProcess/pilot_calendar_shlz_asset_001_test.php` — reproduced exit `255` at the stale Docker `SHLZ_UI_REVISION` pin (`efc4e2e9e8bd4eb900ee8efbb9ba1fb4bdcbce31` instead of approved `a0a8ca6df60b84aa1fe10a1cb500de32dacd4516`)
- Verdict: `APPROVED`

## Findings

None.

- **Traceability:** the test cites `PILOT-CALENDAR-SHLZ-ASSET-001` and directly covers the approved revision, public behavior export, generated Calendar Grid stylesheet, and runtime artifact-copy contract.
- **Public seam:** after its focused Dockerfile guards, the test builds the real `shlz-ui-build` target and probes the resulting artifact image. It does not inspect or copy component implementation source into FMonitor.
- **Checkout identity and rejected-case sensitivity:** the test now requires the exact detached checkout through `${SHLZ_UI_REVISION}` and the build-time `rev-parse HEAD` equality guard. The actual Docker build executes that guard before producing the probed image. Retaining only the approved `ARG` while substituting the checkout therefore fails; missing behavior output, missing bundled styles, an absent public export, and a different runtime artifact source also fail independently.
- **Generated artifacts:** the probe requires a nonempty `packages/behaviors/dist/calendar-grid.js`, the Calendar Grid selector in `packages/styles/dist/shlz.css`, and the public `./calendar-grid` package entry. These assertions exercise generated outputs rather than source-file presence.
- **Expected-value independence:** the revision, generated artifact paths, public behavior name, stylesheet identity, and runtime destination are fixed by the approved product contract and public `shlz-ui` component boundary, not inferred from planned FMonitor implementation output.
- **Determinism and isolation:** the current RED occurs before external setup and is exactly the missing revision pin. Build/process failures and missing image receipts are classified explicitly. The test uses a temporary IID receipt, removes it, runs an ephemeral probe container with `--rm`, and touches no production system or product data.
- **Intended RED:** the focused command reproducibly exits `255` on line 11 with `Pilot image is not pinned to the approved Calendar Grid export revision.` The current Dockerfile contains the predecessor revision while its checkout/equality and runtime-copy plumbing are otherwise present, isolating the missing behavior.

## Required changes

None.

Reviewed-input SHA-256:

```text
57830cdd60ce3b47f9b0bd808b600618de5457ed82738b286445a39a3cf20fb5  specs/PILOT-CALENDAR-SHLZ-ASSET-001.md
e31db00fb0e2cdaa8352e3a074702357d6865d20bfd3e29c9eb22bce87face7f  tests/InstallationProcess/pilot_calendar_shlz_asset_001_test.php
c4bd9335ed6e26418066c5f8c704a8689533dbc4212aa6193af6d1d4c6206108  Dockerfile
```

Gate 3 is approved for these exact specification and test bytes. Gate 4 may implement only enough Docker behavior to make the reviewed test pass without changing its expectations.
