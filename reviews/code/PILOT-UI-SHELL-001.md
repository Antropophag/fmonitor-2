# Code review: PILOT-UI-SHELL-001 v0.4

- Gate: 5 — fresh independent code review
- Reviewer: separately tasked Codex agent `/root/ui_code_review_final`
- Verdict: `APPROVED`
- Specification: `fb4ced72a50296885a86b72a47978f9ad01932b5`
- Approved test review: `634a06030317a1fcae7bbfe6f8a48c5b6726a868`
- Reviewed implementation HEAD: `f6f7efbe821c8f79f5261840a271eb98b1351c7d`
- Reviewed implementation range: `634a060..f6f7efb`
- Full reviewed slice range: `fb4ced7^..f6f7efb`
- Browser evidence: `/home/antropophag/code/fmonitor-2-visual-tools/evidence/final-complete-f6f7efb`
- Review date: 2026-08-29

## Verdict

`APPROVED`. The implementation conforms to `PILOT-UI-SHELL-001 v0.4`, preserves the inherited read-only HTTP contracts, and has complete responsive browser evidence at the exact reviewed head. The three blockers recorded against the earlier `faec7ad` candidate are corrected: CSS assets enforce distinct fixed basenames, configured card capability failures remain fail-closed independently of command readiness, and the evidence covers every required viewport and 200% text case.

## Spec review

No blocking or non-blocking specification findings.

- Configured pages use the shared product shell, two ordered local stylesheets, accessible navigation/breadcrumb composition, one `h1`, native form controls, and route-specific facts and copy.
- Unconfigured compatibility composition preserves predecessor HTML/link behavior and returns exact `404` for `/pilot/assets/pilot.css` without reading that descriptor.
- `ShlzCssAsset` accepts only `shlz.css`; `PilotCssAsset` accepts only `pilot.css`. Both retain the inherited descriptor validation and cleanup boundary.
- Configured object cards always query the exact prepare capability and redact infrastructure failure as `503`, including when `FMONITOR_BUSINESS_DATE` is absent. The narrow compatibility exception remains limited to the explicitly approved unconfigured predecessor condition.
- Views perform no SQL, authorization, command, filesystem, header, or persistence work. DB-derived values remain escaped and GET/HEAD remain read-only.
- The reviewed test exercises the public HTTP seam and contains regression-sensitive cases for cross-wired CSS basenames and configured-card capability failure without business-date configuration.

## Standards review

No documented-standard violation or material security/maintainability defect found.

One non-blocking judgement call remains: compatibility document markup is duplicated across the four renderers, and card/prepare derive compatibility bodies by slicing their configured rendering. This is localized compatibility code required to preserve exact predecessor contracts; it does not put page HTML back in `PilotHttp.php`, cross the specified view/orchestration boundary, or justify delaying this vertical slice.

## Verification evidence

- `php tests/InstallationProcess/pilot_ui_shell_001_test.php` — `PASS: PILOT-UI-SHELL-001 public UI shell`.
- Relevant predecessors (`pilot_http_auth_001`, `pilot_object_list_001`, `pilot_object_card_001`, `pilot_prepare_form_001`) — all `PASS`.
- All 46 `tests/InstallationProcess/*_test.php`, run sequentially in the reviewed worktree — all `PASS`.
- Evidence runtime: Playwright `1.62.1`, Chromium `151.0.7922.34`, Node `v22.23.1`; recorded Git SHA exactly `f6f7efbe821c8f79f5261840a271eb98b1351c7d`.
- Browser matrix: queue, card, and prepare at `1440x900`, `768x1024`, `320x568`, and `320x568` with root font size increased from `16px` to `32px` — 12 successful `200` cases.
- Every recorded case reports `overflow=false`, zero clipped elements, zero over-wide elements, the required heading sequence, both local stylesheet URLs, and `allFocusVisible=true`. Screenshots and separate focus screenshots are present for every case.

## Artifact hashes

```text
b54412b14ca3d3e8ad63fc629d3dda7e5902209c52a1b2acd92dade5ba053531  specs/PILOT-UI-SHELL-001.md
a7fd35251485818c3caad2c8526b49507d5d6bc883ecff33f2898db5879adc2f  tests/InstallationProcess/pilot_ui_shell_001_test.php
a2ef54ff39f550be9b0ccd63d473147b08a894a4fa0573de0ad1b6f72cbf715a  reviews/tests/PILOT-UI-SHELL-001.md
a5bab0a1799188509f9803426c7a86b7347c7ac74889824ec8688160ef4ce228  app/PilotHttp/PilotHttp.php
29469087a3873ea6b0576ebc32f54facbf21d4d1fc27ae28f3a84c3738f92a18  app/PilotHttp/PilotView.php
f4b64b0b1b8c417f4bad23d0a150c5997681db65d4f26347d7b67151047ee538  app/PilotHttp/PilotShellView.php
f931f597e601b395c8e4bd069c8e491cefe9d171d6b165594fad6d676451bca9  app/PilotHttp/ObjectListView.php
f32e816bb77845c5aead611ab534cf5f114536e8e8f23b6f0ef4e0fced74e356  app/PilotHttp/ObjectCardView.php
74e2787b7f2549635ce81ba08b1df7af00f7316bb59e0c620134a4d62658d7ae  app/PilotHttp/PrepareFormView.php
3ac54d11b90484cd7e22ca3df73d348c5b7245726a6d54ded232d05b300a6699  app/PilotHttp/pilot.css
```

Gate 5 for `PILOT-UI-SHELL-001 v0.4` is `APPROVED` at implementation head `f6f7efb`.
