# Code review: OTIZ-SHLZ-UI-001

- Reviewer: Codex independent Gate 5 reviewer `/root/issue196_gate3` (gpt-5.6-sol / low); authored neither production nor tests
- Reviewed source: base `af4e2ddb72a194ecfb114820f4134a34b20fdb39` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T213342Z-0e7b53d964/snapshot/source.patch`, SHA-256 `05ab39c9ef0a39131e9340cc64d4055bad22940d26d336345e266e15cf0b41ff`; candidate source `1d87f7cbcf80c334148e09d678638d0e52b6eb789dd8370f4bfa722187f30281`; executable source `8f54889a6493c5de476f88b533c0096ba4ec28085815daab7258e4d9e893c7ea`
- Specification: `specs/OTIZ-SHLZ-UI-001.md` (`57b40195bca93b98970dc9f5d88813f0b7c5614d9e243dbf62c771c47d1bc8a2`), OpenSpec design and tasks under `openspec/changes/refresh-otiz-shlz-ui/`
- Production delta reviewed: `app/YiiRuntime/Controllers/OtizSettlementController.php` and `app/YiiRuntime/Assets/pilot.css`; the planned `app/YiiRuntime/Views/otiz.php` and `app/YiiRuntime/Views/otiz-snapshot.php` do not exist in the candidate
- Gate 3: complete-matrix test approval and the later root-authored test-delta approval are recorded in `reviews/tests/OTIZ-SHLZ-UI-001.md`
- Verdict: `CHANGES_REQUESTED`

## Evidence

All eight mapped records are exact-source GREEN for candidate `1d87f7cbcf80c334148e09d678638d0e52b6eb789dd8370f4bfa722187f30281` and executable source `8f54889a6493c5de476f88b533c0096ba4ec28085815daab7258e4d9e893c7ea`:

- target browser: `1789767194714054000-16908816f7614b87a950a2e019786eac`
- workflow admission: `1789767194689815000-a6eb5ef655f148289bd18477b7d4ebcc`
- command mapping/rejections: `1789767194711110000-93066ff4d4254139a09d407ec65f3ce4`
- publication: `1789767194723566000-932c679c402749f79e338e90fbea2250`
- settlement owner: `1789767194725283000-3406077b16ed4f80a9e41305462466e9`
- settlement concurrency: `1789767194743319000-aec8178c41df482b966964bf068e0375`
- object register HTTP: `1789767194741813000-bda596ed84af474ab4a9e512f934f363`
- settlement browser: `1789767194760018000-45ad57f0e0494d2a87bb64c7109c6ad3`

The supplied `make architecture-check` result is PASS. The supplied Impeccable mechanical detector result is `[]`; I independently ran the same detector over the two production targets and also received `[]`. These mechanical results do not waive the manual architecture/design findings below.

`php tests/Runtime/runtime_storage_001_test.php` has a direct PASS, but the delivery harness classifies this obligation as `UNKNOWN`; this review records it as **UNKNOWN**, not GREEN. Exact-source CI, deployment and merge readiness are also not established by Gate 5.

## Findings

1. **HIGH — the implementation violates the accepted presentation seam and duplicates the application shell inside the controller.** Locations: `openspec/changes/refresh-otiz-shlz-ui/design.md:21-23,41-45`; `app/YiiRuntime/Controllers/OtizSettlementController.php:21-49`; missing planned files `app/YiiRuntime/Views/otiz.php` and `app/YiiRuntime/Views/otiz-snapshot.php`; existing shared seam `app/YiiRuntime/ViewSupport.php:15-100`.

   The accepted design explicitly says the controller passes projections into Yii views, presentation moves out of concatenated controller strings, and all `/pilot/otiz/**` pages use `ViewSupport::begin/end`. Instead, the change adds the entire payments/register/history/snapshot DOM and a private `shell()` implementation to `OtizSettlementController`. The most important HTML builders are single physical lines of roughly 947–1,971 characters, which also keeps the file below the architecture check's 150-line advisory by formatting rather than by reducing responsibility. The private shell duplicates `ViewSupport` but already diverges from it: it manually links assets instead of registering the asset bundle/view lifecycle, omits the shared favicon/logo SVG/sidebar collapse state, derives only one initial, and owns another logout/CSRF composition.

   Impact: future shell, CSP/asset, accessibility, identity/navigation or logout corrections can land in the canonical `ViewSupport` path while OTIZ silently remains stale. Presentation review and escaping become materially harder, and the controller continues to own HTML in direct contradiction to the slice's primary risk mitigation. This is a hard design/spec conformance failure, not merely a Fowler-style smell. Move the markup into the planned Yii views/partials, call them through the normal Yii render seam, and use `ViewSupport::begin/end`; leave the controller responsible for authorization, projection selection, commands and response mapping. Preserve every approved route/form/DOM behavior and rerun the exact mapped checks because this changes the rendered source.

2. **MEDIUM — the new OTIZ CSS creates a parallel hard-coded color palette instead of composing with the required shared semantic tokens.** Location: `app/YiiRuntime/Assets/pilot.css:1794-1845`; design requirement `openspec/changes/refresh-otiz-shlz-ui/design.md:25-27`.

   The added block repeatedly uses literal colors (`#252a33`, `#dce4ff`, `#17276b`, `#4263d5`, `#dfe3e9`, `#eef1f8`, `#525d70`, `#f7f8fa`, `#667083`, `#fff2dc`, `#51390d`, `#fde8e8`, `#732626`, and others) even though the accepted decision says shared `shlz-ui` semantic tokens provide the visual vocabulary and new `fm2-otiz-*` classes own composition only. Some public `shlz-*` components are used correctly, but their surrounding states, focus, surfaces, borders and text colors form a separate local palette.

   Impact: OTIZ will not inherit future corporate theme/contrast corrections consistently and can drift from adjacent Yii surfaces. Replace available literal colors with the appropriate public semantic custom properties (retaining bounded fallbacks only where this repository convention requires them); keep only genuinely OTIZ-specific layout/composition declarations local. The detector's empty result is acknowledged but does not inspect this design-decision mismatch.

## Standards axis

- Finding 1 is a documented design breach and also exhibits **Divergent Change** and **Duplicated Code** judgment calls: one HTTP adapter owns command mapping, database adapter construction, a full shell and every page composition, while duplicating `ViewSupport`.
- Finding 2 is a documented shared-design-system breach. No new dependency-direction, runtime DDL, SQL-ownership, session, rapid-pilot or public-seam violation was found. The architecture PASS supports those bounded conclusions.
- The legacy KTU display normalization in `allocations()` is not a new formula owner: `specs/OTIZ-SETTLEMENT-001.md:82-88` explicitly preserves that renderer interpretation and `MariaDbOtizSettlementView` already applies the same rule for workbook output.

## Spec and invariant axis

Apart from the presentation-seam findings, the implementation conforms to the observable A1–A8 behavior exercised by the approved matrix: common OTIZ navigation/current state, workflow header/action hierarchy, object-scoped trace/allocation/issues, labelled rows, contained ledger scroll, responsive/zoom/keyboard/coarse-pointer/reduced-motion/JS-off behavior, edge states and live feedback. Forms preserve methods, routes, CSRF and identifiers. Command owners remain in `app/Otiz`; no schema, formula, permission or persistence writer was added. Exact-source publication/settlement/replay/concurrency/denial/no-fact tests are GREEN.

Security review found no unescaped dynamic HTML introduced at the reviewed render points: user/domain strings pass through `e()`, numeric identifiers are cast, and request URLs pass through the escaped tab helper. AccessControl and VerbFilter remain in force before action parsing. No secret exposure, client-side authority or new write seam was found.

## Impeccable technical audit

| Dimension | Score | Key result |
|---|---:|---|
| Accessibility | 4/4 | Semantic regions, labels, live roles, focus, keyboard and reduced-motion behavior are covered by exact browser evidence. |
| Performance | 4/4 | SSR is lean; no new dependency, image payload, client render loop or expensive animation was introduced. |
| Responsive design | 4/4 | Required widths, 200% layout equivalent, contained overflow and coarse targets are exact-source GREEN. |
| Theming | 2/4 | Functional contrast is evidenced, but the local hard-coded palette bypasses shared semantic tokens. |
| Implementation integrity | 2/4 | Product-specific UI is coherent, but controller-owned markup and a cloned shell contradict the accepted system architecture. |
| **Total** | **16/20 — Good** | Two release findings: one HIGH, one MEDIUM. |

Implementation-integrity verdict: **FAIL for Gate 5** until the accepted ViewSupport/Yii-view boundary is restored. Detector result `[]` is a verified true negative for its mechanical rules, not proof of architectural conformance.

Positive findings to preserve: semantic HTML and object association are strong; mobile strategies are explicit; action consequences are visually and textually distinct; no-JS and reduced-motion paths retain content; CSS is scoped under OTIZ roots; the change uses public `shlz-*` primitives; and domain/security invariants have broad exact-source evidence.

## Required changes

- Move OTIZ presentation and the shell into the planned Yii views/partials using canonical `ViewSupport`; reduce `OtizSettlementController` to HTTP/application orchestration without changing approved behavior.
- Replace the new parallel literal palette with shared public semantic tokens where equivalents exist.
- Prepare a new exact-source package and rerun the affected target browser, settlement browser, workflow/command regressions, architecture check and required Gate 5 review. Preserve `runtime_storage` as `UNKNOWN` unless the harness supplies qualifying evidence.

## Exact-source re-review — candidate `4accc142…`

- Reviewer: Codex independent Gate 5 re-reviewer `/root/issue196_gate5_r2` (gpt-5.6-sol / low); authored neither production nor tests
- Reviewed source: base `af4e2ddb72a194ecfb114820f4134a34b20fdb39` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T093702Z-b76a1e30c7/snapshot/source.patch`, SHA-256 `0c148c6e8a7b3d4db4c96c6cf7adfc7e2ba7724de38110d9eacc625ea9e319f0`; candidate source `4accc14292feb12169e02cd74a081c9062763fac2f48ecec492090be2e1ffa0b`; executable source `9ff9e576e16a8ca5ccbb75fe324fb433153ea9c23766e5a46ec852ac5dbc293d`
- Evidence: all 12 package records are exact-source `GREEN`, including the target browser/UI, mapped financial contracts, deployment/governance/runtime-storage and architecture-guard checks. Separate exact-source `make architecture-check` record `1789810525815239000-0feebabdf14749ae9075625ba3c90111` is also `GREEN` but is outside the package plan. CI, PR, deployment and merge readiness remain `UNKNOWN`.
- Verdict: `CHANGES_REQUESTED`

### Re-review findings

1. **MEDIUM — the new OTIZ CSS violates the inherited application stylesheet prohibition on `!important`.** Locations: `app/YiiRuntime/Assets/pilot.css:1848-1849`; governing rule `specs/PILOT-UI-SHELL-001.md:40-45`.

   The added narrow-screen touch-target rule uses `min-width: 48px !important` and `min-height: 48px !important`; the added reduced-motion rule uses `animation-duration: 0s !important` and `transition-duration: 0s !important`. The approved shared `pilot.css` contract explicitly says application CSS does not use `!important`. Existing historical occurrences elsewhere in the asset do not authorize new violations. Replace these declarations through bounded selector specificity, rule ordering or an application-owned custom property while preserving the exact coarse-pointer and reduced-motion behavior, then prepare new exact-source evidence.

2. **LOW — duplicated responsive declarations increase drift risk (Fowler `Duplicated Code`, judgement call).** Location: `app/YiiRuntime/Assets/pilot.css:1842-1848`. OTIZ tab wrapping is repeated at overlapping `760px` and `900px` breakpoints, while touch-target sizing is repeated across overlapping coarse-pointer and width rules. Consolidate each invariant and layer only the genuinely different size where needed.

3. **LOW — presentation primitives are repeated across views (Fowler `Duplicated Code` / `Primitive Obsession`, judgement call).** Locations: `app/YiiRuntime/Views/otiz.php:5`, `app/YiiRuntime/Views/_otiz-snapshot-list.php:2-3`, `app/YiiRuntime/Views/otiz-snapshot.php:6-7`. Centralize money/date display in `ViewSupport` or a small shared formatter so cents and ISO dates have one presentation owner.

4. **LOW — `$p` obscures the projection concept (Fowler `Mysterious Name`, judgement call).** Location: `app/YiiRuntime/Controllers/OtizSettlementController.php:16`. Rename it to `$projection`.

### Standards axis

The `!important` rule is one documented-standard breach and blocks approval; the other three findings are non-blocking maintainability judgements. The prior HIGH controller-owned presentation/cloned-shell finding is fully resolved: the controller renders Yii views and both page views use canonical `ViewSupport::begin/end`. The prior MEDIUM literal-palette finding is fully resolved: the added OTIZ block contains no raw hex/rgb/hsl palette and consumes public `--shlz-*` properties. No new security, persistence, SQL/DDL ownership, dependency-direction or integration-boundary breach was found.

### Spec and invariant axis

`OTIZ-SHLZ-UI-001` A1-A8 pass this re-review. Routes, methods, CSRF and form-field names, authorization priority, command owners, formulas, append-only facts, replay/concurrency outcomes and SSR behavior remain covered by exact-source GREEN evidence. No scope creep, new dependency, schema/writer or unescaped dynamic output was found. The spec axis is `APPROVED`; it does not override the separate standards-axis failure.

### Required correction

- Remove the four newly added `!important` declarations without weakening the tested touch-target or reduced-motion guarantees, rerun the affected focused checks, prepare a new exact-source package and obtain a fresh independent Gate 5 decision. The LOW maintainability items should be corrected in the same bounded pass where safe or explicitly dispositioned before resubmission.

## Final exact-source re-review — candidate `0c05ab24…`

- Reviewer: Codex independent Gate 5 re-reviewer `/root/issue196_gate5_r2` (gpt-5.6-sol / low); authored neither production nor tests
- Reviewed source: base `af4e2ddb72a194ecfb114820f4134a34b20fdb39` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T094640Z-a117dd9f9d/snapshot/source.patch`, SHA-256 `aec33aeb7ad48aeb26735d6e7a341507a9d2d5033edd6ea9cd506c87962ccf34`; candidate source `0c05ab24a0370aa8ee719eda16d3b07fba7693e16a0fea9349a545f17da7f271`; executable source `41b0ec3e98aaf115afe5ea88d9a60a302fde954a3b1613f796b1e20c7d19b16f`
- Evidence: all 12 plan records are exact-source `GREEN`, exit `0`, with no source drift. Separate exact-source `make architecture-check` record `1789811125032956000-73629cd08267430c8a12f9959336506e` is also `GREEN` but outside the plan package. CI, PR, deployment and merge readiness remain `UNKNOWN`.
- Verdict: `APPROVED`

### Final findings

No blocking or release findings.

The prior `!important` finding is fully resolved. The new OTIZ rules contain no `!important`: `app/YiiRuntime/Assets/pilot.css:1847` uses a later, bounded high-specificity selector to preserve 48×48 mobile targets; line 1846 preserves the normative 44×44 coarse-pointer minimum. Line 1848 disables animation and transitions under reduced motion without importance. The modified pre-existing global reduced-motion rule at line 1245 retains its historical `!important` declarations but now consumes `--fm2-motion-duration` with the original `.01ms` fallback, allowing the scoped OTIZ value `0s`; it does not introduce a new prohibited declaration. Exact browser evidence measures visible coarse targets and effective motion and is GREEN.

The earlier HIGH controller-owned presentation/cloned-shell finding remains resolved through Yii views and canonical `ViewSupport::begin/end`. The earlier MEDIUM literal-palette finding remains resolved; the added OTIZ composition consumes public `--shlz-*` properties without raw color literals. The complete diff introduces no new RBAC, CSRF, escaping, mutation ownership, SQL/DDL, dependency or integration-boundary defect. `OTIZ-SHLZ-UI-001` A1-A8 remain satisfied, including routes, fields, financial/domain invariants, append-only history, replay/concurrency and SSR behavior.

Two non-blocking maintainability observations remain: repeated money/date presentation closures in the OTIZ views (`Duplicated Code` / `Primitive Obsession`) and the controller-local `$p` projection name (`Mysterious Name`). They do not change behavior, security or the accepted seam and do not block this bounded delivery; they may be handled in a later refactor rather than expanding this correction.

Standards axis: `APPROVED`. Spec and invariant axis: `APPROVED`. Gate 5: `APPROVED` for exact candidate `0c05ab24a0370aa8ee719eda16d3b07fba7693e16a0fea9349a545f17da7f271`.

## Stand-feedback correction final review — candidate `6154d14e…`

- Reviewer: Codex independent Gate 5 reviewer `/root/otiz_final_review`; authored neither production nor tests.
- Reviewed source: base `7784d3f800dfe53fecf9fb3ee8a8bce7daa31192` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T120028Z-0d9b4c2b64/snapshot/source.patch`, SHA-256 `a226c8f21563801c407e014f9bed1295507e03645e5a30ebcf4d49cf436cd2b6`; candidate source `6154d14eecaa8741884d9e43dafc827d2de33a5e9ef96b4aca09a92476d918bd`; executable source `8057437b279b81b678acb9050013b48cb33fab464e395f0488d72357f25de89a`.
- Contracts: `specs/OTIZ-SHLZ-UI-001.md` and `specs/OTIZ-OBJECT-REGISTER-PAGING-001.md`; prior complete-matrix Gate 3 approval is recorded in `reviews/tests/OTIZ-SHLZ-UI-001.md`.
- Evidence: all 14 supplied package records are exact-source `GREEN`, covering the OTIZ browser, shared renderer, both legacy-material readers, the three adjacent pagination consumers, existing financial/workflow/command/browser contracts, publication, settlement owner/concurrency, and object-register HTTP. `git diff --check` is clean. Exact-source GitHub CI, deployment, publication and merge readiness are not established by this package and remain `UNKNOWN`.
- Verdict: `CHANGES_REQUESTED`.

### Finding

1. **HIGH — the displayed per-row remaining fund uses the global stored-deadline formula instead of the required trace-derived row formula.** Locations: `app/Otiz/ObjectEconomy.php:26-30`; rendered consumer `app/YiiRuntime/Views/otiz.php`; contract `specs/OTIZ-OBJECT-REGISTER-PAGING-001.md:12,49`.

   `deadline_penalty_cents` is correctly derived from the saved calculation trace as `max(0, trace_progress_cents - accrued_cents)`, and `penalties_cents` uses that trace-derived amount. But the new `remaining_fund_cents` subtracts `deadline_cents`, the aggregate stored closure fact. The accepted characterization deliberately preserves two different calculations: global `summary.balance` subtracts stored deadline closures, while each row balance subtracts the trace-derived deadline penalty. Consequently, whenever the stored closure amount differs from the calculated trace penalty, the new nine-column register shows an internally inconsistent row: “Удержано” reflects trace penalty but “Остаток фонда” reflects stored deadline closure, and the latter violates A9 and the paging contract.

   Correct `remaining_fund_cents` to subtract `deadline_penalty_cents` while leaving `ObjectEconomy::accumulate()` on stored `deadline_cents`. Add a sensitive assertion with deliberately unequal stored and trace deadline values that verifies both the public row projection and rendered “Остаток фонда”; the current GREEN fixtures do not distinguish the two formulas. Because this changes tests and production semantics, recompute the plan as required, obtain the applicable independent test approval, prepare a new exact-source package and rerun the affected register/browser and financial regressions before Gate 5 re-review.

### Complete review disposition

The pagination implementation otherwise follows the public `shlz-ui` contract: semantic `nav > ul > li`, link destinations, current/disabled/ellipsis states, icons, summaries, purpose-specific labels, and preserved query context are present across OTIZ, objects, installers and construction control. Page sizes match the corresponding consumers. The asset route is narrowly allowlisted and resolves existing public icon assets.

Numeric material compatibility is implemented through the existing `NativePremiumNorms` owner and is consumed by both the SQL register and native premium-input reader: `86`/`112` map to `11500bp`, `123` to `12500bp`, and unknown codes remain unresolved. The supplied tests fingerprint all fixture tables before and after both reads; no new writer, schema mutation, RBAC change, formula owner, state-changing seam, or history rewrite was found. Authorization, CSRF, escaping and server-side pagination boundaries remain intact.

The earlier Gate 5 corrections for canonical Yii views/ViewSupport, shared semantic color tokens and the stylesheet `!important` prohibition remain intact. No additional blocking findings were found beyond the row-balance defect above.

### Required changes

- Use `deadline_penalty_cents`, not stored `deadline_cents`, in `remaining_fund_cents`; preserve the existing stored-deadline formula only for global `summary.balance`.
- Add a regression that makes stored and trace deadline amounts unequal and asserts the public row plus rendered remaining-fund cell.
- Recompute the verification plan/test-review requirement, prepare new exact-source GREEN evidence, and obtain a fresh independent Gate 5 decision. Keep CI/deployment/publication/merge status `UNKNOWN` until qualifying evidence exists.

## Stand-feedback correction final re-review — candidate `7ddb5449…`

- Reviewer: Codex independent Gate 5 reviewer `/root/otiz_final_review`; authored neither production nor tests.
- Reviewed source: base `7784d3f800dfe53fecf9fb3ee8a8bce7daa31192` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T121539Z-f9f765f89c/snapshot/source.patch`, SHA-256 `09c67027e9ce52349086cba4d6a40c15c33e5fa61bf29b9426ee9ab0ec4ab506`; candidate source `7ddb5449c6ee48fdfd2638093f30bdab3059af762fb0c7a7305f6a3f7a9ce019`; executable source `8057437b279b81b678acb9050013b48cb33fab464e395f0488d72357f25de89a`.
- Verification plan: SHA-256 `f5d25d3e981f47f5428bfcacb35db2fe57089b983d019eea4f3f2f00f20471eb`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Test review: the post-Gate-5 row-balance delta is independently `APPROVED` in `reviews/tests/OTIZ-SHLZ-UI-001.md`, with intended RED evidence at both the public domain and authenticated rendered seams.
- Evidence: all 14 supplied package records are exact-source `GREEN`. In particular, `object_register_paging_001_test.php` record `1789906402774614000-f106c2c34bcd41b6b6128b3225a8cc4f` proves the domain value, and `yii2_otiz_shlz_ui_001_test.php` record `1789906389361835000-438ec92893884200a2e451a2a7c67ebb` proves the rendered value. The shared renderer, both legacy-material readers, three adjacent pagination consumers, existing OTIZ workflow/commands/browser, publication, settlement owner/concurrency and register HTTP regressions are also GREEN on the same source. `git diff --check` is clean.
- Verdict: `APPROVED`.

### Prior finding disposition

1. **Prior HIGH row-balance finding: RESOLVED.** `ObjectEconomy::decorate()` now computes `remaining_fund_cents` as `max(0, fund - paid - discipline - deadline_penalty_cents)`, where `deadline_penalty_cents` is derived from the saved calculation trace. `ObjectEconomy::accumulate()` deliberately remains unchanged and computes global `summary.balance` with stored `deadline_cents`, preserving the contract's characterized row/global distinction.

   The approved sensitive fixtures make the operands unequal rather than allowing accidental equivalence. The domain test proves fund `52,000,000`, discipline `10`, stored deadline `20`, trace deadline `50` yields row remaining `51,999,940`; the browser test independently proves fund `59,800,000`, discipline `10`, trace deadline `50` renders held `0.60 ₽` and remaining `597,999.40 ₽` in their exact labelled cells. Existing global-summary assertions remain GREEN without expectation changes, so the correction did not collapse the stored-deadline summary behavior.

### Complete final assessment

No findings remain. The full prior scope remains conformant: the nine-column OTIZ register preserves known/unknown monetary presentation, server paging/filter/sort/page-size behavior and global summaries; public `shlz-pagination` composition remains shared across OTIZ, objects, installers and construction control; numeric material codes `86`, `112`, `123` and unknown values behave correctly through both readers with provenance and no-write sensitivity. No new mutation owner, fact rewrite, schema/DDL, RBAC, CSRF, escaping, dependency-boundary or history violation was introduced.

Standards axis: `APPROVED`. Spec and invariant axis: `APPROVED`. Gate 5: `APPROVED` for exact candidate `7ddb5449c6ee48fdfd2638093f30bdab3059af762fb0c7a7305f6a3f7a9ce019`.

Exact-source GitHub CI, deployment, publication and merge readiness are outside this review package and remain `UNKNOWN`; this Gate 5 approval does not promote them to GREEN.

## Owner-confirmed dictionary/table visual final review — candidate `1c330fd5…`

- Reviewer: Codex independent Gate 5 reviewer `/root/otiz_final_review`; authored neither production nor tests.
- Reviewed source: base `7784d3f800dfe53fecf9fb3ee8a8bce7daa31192` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T124818Z-39ad966988/snapshot/source.patch`, SHA-256 `615893dd5c78fbf73f817bf94c24212423ea647f9d68861a4d8eb93bb2c842b7`; candidate source `1c330fd5a7268927405f92f7e2761e21da2d72fa2f07707a9ec5d5e5dee24f20`; executable source `8057437b279b81b678acb9050013b48cb33fab464e395f0488d72357f25de89a`.
- Verification plan: SHA-256 `9c57d009d01b2e50b754dc2327f8bfdb5725c3fa4e16c9e764b7159eb28f0260`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Gate 3: the corrected owner-confirmed dictionary/table test candidate is independently `APPROVED` in `reviews/tests/OTIZ-SHLZ-UI-001.md`.
- Evidence: all 14 supplied package records are exact-source `GREEN`; `git diff --check` is clean. Exact-source GitHub CI, deployment, publication and merge readiness remain `UNKNOWN`.
- Verdict: `CHANGES_REQUESTED`.

### Finding

1. **MEDIUM — the new white surfaces reintroduce raw color literals after the accepted semantic-token correction.** Locations: `app/YiiRuntime/Assets/pilot.css:1812,1832`; accepted design `openspec/changes/refresh-otiz-shlz-ui/design.md:25-27`; prior Gate 5 literal-palette disposition in this review record.

   The correction adds `background: #fff` to `.fm2-otiz-page` and `.fm2-otiz-register-wrap`. The accepted design requires shared `shlz-ui` semantic tokens to own the visual vocabulary and OTIZ classes to own composition only. The earlier Gate 5 palette finding was explicitly resolved on the basis that the OTIZ block contained no raw color literals. The public asset already defines `--shlz-semantic-color-surface-base: #FFFFFF` and consistently uses it for white surfaces, so these declarations bypass an existing exact semantic equivalent.

   Replace both new literals with `var(--shlz-semantic-color-surface-base)`. The approved browser assertion measures computed `rgb(255, 255, 255)`, not the implementation spelling, so it remains sensitive to the owner-confirmed white result while also preserving the design-system boundary. Prepare a new exact-source package and rerun the target browser plus the unchanged focused matrix before final re-review.

### Complete scope disposition

No other findings remain. `NativePremiumNorms` maps the owner-confirmed read-only dictionary exactly: `41` and `72` to `10000bp`, `86` and `112` to `11500bp`, and `123` to `12500bp`; unknown numeric codes remain unresolved. Both the public object-register reader and native premium-input reader cover all five mappings or their established initial case, exact provenance where applicable, unknown refusal/missing-norm behavior, and immediate before/after fact fingerprints. No detail snapshot, domain fact, schema or history is written by the reads.

The rendered register uses `thead.shlz-table__head`, `tr.shlz-table__row`, scoped `th.shlz-table__cell`, and `td.shlz-table__cell`; exactly seven numeric headers and seven numeric cells per data row carry `shlz-table__cell--numeric`, while Object and State do not. The authenticated browser evidence proves both requested surfaces compute to white. The prior row/global deadline distinction, pagination consumers, authorization, CSRF, escaping and append-only invariants remain GREEN and unchanged.

### Required change

- Replace the two new `#fff` declarations with `var(--shlz-semantic-color-surface-base)`, retain computed-white browser coverage, prepare new exact-source GREEN evidence, and obtain a fresh Gate 5 decision.

## Owner-confirmed dictionary/table visual final re-review — candidate `ee783ae4…`

- Reviewer: Codex independent Gate 5 reviewer `/root/otiz_final_review`; authored neither production nor tests.
- Reviewed source: base `7784d3f800dfe53fecf9fb3ee8a8bce7daa31192` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T125343Z-6db1e4b367/snapshot/source.patch`, SHA-256 `8579007944764daa4acc2fa076eb988f9c1bc696f223e63f7d5ecc767f356f4a`; candidate source `ee783ae41a5b0cd64fea4f490fa1680fe55487cfae8a8dafb4407bbc610b17ce`; executable source `8057437b279b81b678acb9050013b48cb33fab464e395f0488d72357f25de89a`.
- Verification plan: SHA-256 `c4b32e848eca5fc6f775cddab4261c4dbc629dbce6edfa8ec6c7a8e95b53cc76`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Evidence: all 14 supplied package records are exact-source `GREEN`, including target browser record `1789908623770134000-2ee9392f33094227b55c130515e906d2`, register dictionary/no-write record `1789908632815188000-d832ce5dce27418ea2912a58f1b300ab`, and premium-input dictionary/provenance/no-write record `1789908657048622000-fea6ee6f84f246b89c034b4988672b68`. The shared pagination and adjacent consumers, existing OTIZ workflow/commands/browser, publication, settlement owner/concurrency and register HTTP regressions are GREEN on the same source. `git diff --check` is clean.
- Verdict: `APPROVED`.

### Prior finding disposition

1. **Prior MEDIUM semantic-token finding: RESOLVED.** `.fm2-otiz-page` and `.fm2-otiz-register-wrap` now use `background: var(--shlz-semantic-color-surface-base)`. No raw color literal was added to the OTIZ block. The authenticated browser still proves that both surfaces compute to `rgb(255, 255, 255)`, satisfying the owner-confirmed white appearance through the public design-system token.

### Complete final assessment

No findings remain. The owner-confirmed material dictionary is exact and shared by both public read paths: `41`/`72 → 10000bp`, `86`/`112 → 11500bp`, `123 → 12500bp`; unknown numeric codes remain unresolved. Register and premium-input tests retain immediate complete fact comparisons, exact premium operand provenance, and unknown refusal/missing-norm assertions, so no imported detail, business fact, schema or history mutation is hidden.

The nine-column register retains the exact public table composition: `thead.shlz-table__head`, `tr.shlz-table__row`, scoped `th/td.shlz-table__cell`, seven numeric headers and seven numeric body cells per row using `shlz-table__cell--numeric`, with Object and State nonnumeric. White computed surfaces, per-column economics, unknown presentation, row/global deadline distinction, pagination, authorization, CSRF, escaping and append-only invariants all remain exact-source GREEN.

Standards axis: `APPROVED`. Spec and invariant axis: `APPROVED`. Gate 5: `APPROVED` for exact candidate `ee783ae41a5b0cd64fea4f490fa1680fe55487cfae8a8dafb4407bbc610b17ce`.

Exact-source GitHub CI, deployment, publication and merge readiness are outside this package and remain `UNKNOWN`; this approval does not promote them to GREEN.

## PR #208 CI-correction final re-review — candidate `e9e94715…`

- Reviewer: Codex independent Gate 5 reviewer `/root/otiz_final_review`; authored neither production nor tests.
- Reviewed source: prepared package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T132227Z-9e2478e9fd/package.json`; candidate source `e9e94715e8272698473469a81314d22d0486fe40cff639a1b645071bb90b5d07`; executable source `cf503419babd9743717b957e649e14c635825cf69e0197923fc57870bc749997`.
- Verification plan: SHA-256 `c5b53755f7c54cf94546577c1bec4c766f7fc44588bde315495c4bf7189c20b8`; lane `CRITICAL`; required reviews `gate3`, `final`.
- CI failure inventory for run `35512305480`: `e2e` failed on the stale installer pager selector; `Integration (1/2)` failed on the stale `pilot.css` digest; `Integration (2/2)` failed on a transient truncated/empty worker arrival JSON. Governance, fast and unit were GREEN. The complete inventory contained exactly those three `REGRESSION_FAILURE` entries.
- Evidence: all 14 mapped focused records are exact-source `GREEN`; the installer browser, production web cutover and worker signal tests are separately GREEN after diagnosis. `change_verification_001_test.py` is 18/18 GREEN, `architecture_guard_001_test.py` is 59/59 GREEN, PHP/Node syntax and `git diff --check` are clean.
- Verdict: `APPROVED`; findings absent.

### Delta assessment

The installer browser assertion now targets the public `nav.shlz-pagination` composition and selects the exact page-number link, retaining keyboard navigation and `aria-current="page"` sensitivity without matching the next-arrow destination. The production cutover contract pins the actual reviewed `pilot.css` SHA-256 `0945a22751dabac986252c37ba6985449430b696aaf868b1e6b0c58e82a69bd7`. Both changes update stale expectations; neither weakens the checks or changes production behavior.

The worker-signal failure is unrelated to this delta and reproduced GREEN locally without a source change. A new qualifying exact-source GitHub CI run is still required; publication and merge readiness remain `UNKNOWN` until it succeeds.
