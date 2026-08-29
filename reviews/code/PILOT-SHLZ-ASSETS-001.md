# Code review: PILOT-SHLZ-ASSETS-001 v0.1

- Gate: 5 — fresh independent code review
- Reviewer: separately tasked Codex agent `/root/shlz_assets_code_review_final`
- Verdict: `CHANGES_REQUESTED`
- Specification: `dd22aa883407e19f94d5990bb39ff7cce5bf1712`
- Approved test review: `7ba5f2b`
- Reviewed implementation HEAD: `2186132152d8a7d466e7c4b3b3addca4c7220a7d`
- Core implementation: `4ab55bdaeb7594a275533a11379d8ef25f49d865`
- Corrective implementation: `bdacfec5d3b721d0a4036c706e06006a7b53f27e`
- Review date: 2026-08-29

## Verdict

`CHANGES_REQUESTED`. The focused asset contract, all 49 InstallationProcess tests, and PHP lint pass at the exact reviewed head. The parser correction closes the previously reported malformed import-suffix defect, and the supplied Chromium run proves successful CSS responses and responsive rendering. Gate 5 nevertheless remains closed because production composition leaks process-external resources and the browser record omits one explicit section 6 oracle.

## Standards

### Blocking — persistent SysV shared-memory leak and undeclared runtime coupling

`app/PilotHttp/PilotHttp.php:93` calls `shm_attach()` for every new PID/canonical-entry key, but only calls `shm_detach()`. Neither `ShlzCssManifest::close()`, production composition close, nor demo cleanup calls `shm_remove()`. Inspection after the focused/full test run showed many unattached 131072-byte segments (`nattch=0`) still present in `ipcs -m`, so these are persistent kernel resources rather than composition-lifetime state.

This also adds an undocumented hard dependency on PHP `sysvshm`. The 32-bit CRC key can be reused after PID cycling or collide with an unrelated segment, making a valid composition compare against a stale snapshot and fail. This violates the development-process Gate 5 quality/integration-boundary requirement. Replace the coordination with lifecycle-owned state that is explicitly removed, or avoid process-external state, and cover cleanup/stale-key behavior through the required SSD/TDD gates.

### Non-blocking — Divergent Change smell

`app/PilotHttp/PilotHttp.php:79-102` combines descriptor identity validation, graph traversal, CSS grammar parsing, path resolution, resource bounds, and IPC coordination in dense single-line methods. This is a judgment-call maintainability smell, not a separate rejection: split the named concerns or at least format the control flow so security-sensitive behavior is reviewable.

## Spec

### Blocking — computed-style evidence lacks the required ownership oracle

Specification section 6.3 requires the Chromium oracle to record the property/value **and owning stylesheet URL** for an applied public `.shlz-*` component rule, a `--shlz-*` custom property, and an application `.fm2-*` rule. Evidence at `~/code/fmonitor-2-visual-tools/evidence/final-pilot-acceptance-2186132/` is tied to exact SHA `2186132` and records Chromium `151.0.7922.34`, screenshots, CSS responses/MIME, focus, and overflow. However, `report.json` records only aggregate computed values for `link`, `status`, and `main`; it records neither a `--shlz-*` property/value nor the owning stylesheet URL for any of the three required rule classes. `responsive-final-report.json` likewise contains no computed-rule ownership data.

The screenshots and successful `shlz.css` request cannot replace this explicit oracle. Record the missing exact-head evidence after the implementation correction.

### Resolved prior findings

- `bdacfec` validates the supported `layer`/`supports`/media import suffix grammar; the independently corrected test at `c443f3c` is approved by `7ba5f2b` and passes.
- Exact manifest routing, malformed-route/method priority, unknown-route behavior, traversal/symlink/identity protections, graph limits, GET/HEAD byte and MIME parity, authorization bypass for public assets, cookie/session absence, and fail-closed graph handling pass at the public seam.
- `2186132` strengthens post-bind demo smoke classification and validates all graph members, root HEAD parity, exact lengths, and an unknown manifest route.

## Verification evidence

- `git rev-parse HEAD` — exact `2186132152d8a7d466e7c4b3b3addca4c7220a7d`.
- `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS.
- All 49 `tests/InstallationProcess/*_test.php`, sequentially — PASS.
- `find app bin public tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l` — PASS.
- Chromium evidence inspected at `~/code/fmonitor-2-visual-tools/evidence/final-pilot-acceptance-2186132/`: exact-head screenshots and responsive/assets records present; required ownership/custom-property oracle absent.
- `ipcs -m` after verification — numerous unattached 131072-byte segments remain; review did not remove them.

Gate 5 remains closed for `PILOT-SHLZ-ASSETS-001 v0.1` at `2186132`.
