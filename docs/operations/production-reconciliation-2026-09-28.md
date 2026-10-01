# Production reconciliation — 2026-09-28

Owner request: preserve all fixes already running in production as a separate Git change, verify locally and through GitHub, and keep unshipped issue #257 separate. This change does not authorize production installation, container recreation, migrations, or production-data writes.

## Captured scope and contract

Production checkout HEAD: `a69be7a5b879206f071926d3e270f538da7d03a2`. Parent chain contains six already deployed commits absent from main `145cebd2`: OTIZ reference layout, imported-card norm fallback, native admission, dialog/installer picker layout, isolated opening date-picker styles, and contextual calculation rows/actions. Preserve this history.

Read-only source capture contains 13 modified tracked paths and 20 added guide paths. Application bytes match the running PHP container. Reconcile these existing behaviors:

- Excel KTU is the participant's normalized contribution within the saved object slice, bounded 0–1; Russian status labels; saved financial amounts unchanged.
- Payment register starts with «Все»; installer dashboard shows busy/free bars; object queue has a narrow `%` column using existing completion progress.
- PTO/declaration register and navigation require administrative permission as well as object reading; direct unauthorized access remains denied.
- Calendar is readable by authenticated users; construction-control readers can see all eligible objects, and «Мои» still filters by current assignment. Scheduling/writing permissions remain unchanged.
- OTIZ draft/group rows expose dismissed-recipient warnings without changing payment decisions or money.
- Public `/help/` contains the captured Russian guide and synthetic screenshots, strict static routing and navigation links. Application routes retain authentication.

No new product logic is introduced during capture. Tests characterize the deployed behavior. Issue #257 incident tables, correction logic, new assets and schema v38 are excluded; schema remains v37.

## Provenance and checks

Read-only capture, SHA-256 inventories and original file payloads are retained outside Git:
`/Users/antropophag/.local/share/fmonitor-2/prod-reconcile-20260928/`.
The source branch is `codex/reconcile-production-20260928`, isolated from the active issue #257 worktree. Root restores the existing source and supplies regression checks; original authorship remains in the six commits and earlier delivery records. An independent reviewer checks the assembled candidate.

Focused local checks cover workbook values, admission/portfolio, calendar, construction-control read/write scope, navigation, object queue, dashboard and OTIZ HTTP. Full local `make test` / `make verify` is forbidden; the existing GitHub consumer runs the full matrix for the final committed candidate. Missing/failed checks remain explicit, never overall GREEN.

Production guide files live in the gateway persistent volume, not the PHP image. The source capture retains their checked-in form and Caddy configuration. PHP source matches the host, while worker/scheduler app source has pre-existing differences; the private manifest lists them. This PR does not synchronize those containers. A later release must rehearse consistent application/worker versions and preserve the guide route.

## Owner continuation — 2026-10-01

The owner explicitly requested comparing production against PR #286, adding subsequent deployed changes, fixing all failed checks and merging the verified PR. No production deployment or data mutation is requested. The existing issue #257 WIP stays separate.

Read-only SSH capture on 2026-10-01 found the same production HEAD and exactly one changed application file versus PR head `97884878631303d46cb4cf31ab83231517e266e9`: `app/Otiz/OtizSettlementV2Workbook.php`. All 996 application/runtime files match the host; every other runtime file matches the PR. Published guide hashes match the captured guide. Evidence: `/Users/antropophag/.local/share/fmonitor-2/pr286-20261001/` (`capture.json`, `runtime-audit.json`).

The already deployed export correction displays the saved object distribution pool in the main workbook's «Фонд распределения» column: saved gross amount minus saved deadline deduction, before worker allocation. For example 900,000 cents gross minus 50,000 cents deadline deduction displays 8,500 rubles. Zero deadline deduction preserves gross; missing gross or deadline evidence leaves the cell blank. Draft, history and payment exports share this mapping. Other object-sheet gross amounts, recipient amounts, payment appendix totals, saved facts and authorization remain unchanged. Export never writes or recomputes settlement decisions. Root authors characterization tests before importing the captured source; the production source author is pre-existing and unknown in this continuation.

The failed CI to diagnose is run `36391356465` from 2026-09-28, not September 21. Preserve its complete failed-job and regression inventory before corrections or publication. Full local suites remain forbidden.

### CI repair acceptance matrix

Root retains all 17 failed regressions and the three architecture findings in the private `regression-inventory.json` and job logs. Tests must continue checking exact saved money/source identities, denied writes, audit immutability, real HTTP draft creation/deletion and browser completion, while updating obsolete presentation expectations to the captured production behavior. The dashboard displays six weeks with two busy/free bars; the other forecast buckets remain available through existing detail routes. Calendar and construction-control global reading do not grant scheduling writes. The positive PTO-register fixtures must explicitly possess both `access.administer` and `objects.read`; a reader without administration remains denied. Runtime assets must match the captured current bytes, retaining all existing content-type/cache/security-header assertions.

The architecture checker currently mistakes JavaScript DOM `select` tokens for SQL. The correction may recognize literal selector-list arguments to DOM query/closest APIs and unquoted JavaScript identifiers. Real SQL strings, concatenated SELECT fragments, SQL on the same line as a selector, and all existing PHP/DDL/ownership rules must continue failing with unchanged fingerprints. No baseline or policy exemption is authorized. `tests/Verification/architecture_js_select_tokens_286_test.py` supplies independent public-CLI positive/negative fixtures; existing architecture regression tests remain mandatory focused checks. This check-policy correction retains Gate 3 and final review.

Root authors the test/spec changes, while a separate sol/low executor will implement the checker correction and import the captured workbook source only after Gate 3. Independent sol/low review remains mandatory; the owner explicitly declined substituting another model when the first two attempts encountered capacity errors.

### Runtime CSP correction discovered during continuation

With obsolete selectors corrected, the production-runtime browser successfully creates a nonempty draft, downloads XLSX and accepts the calculation, but records CSP violations from inline `col style="width:…"` in `_otiz-v2-group-table.php`. This is a real deployed defect. Move the exact object-column widths (30%,16%,14%,17%,19%,4%) and recipient-column widths (42%,9%,17%,27%,5%) to scoped external CSS classes. Preserve table order, grouping, totals, responsive behavior and the strict `style-src 'self'` policy. No unsafe-inline, exception or filtered console error is allowed. The existing `tests/Runtime/production_runtime_browser_001_test.php` is the real HTTP/browser regression seam: the complete creation/download/acceptance flow must succeed with zero console/page/request errors. Raw intended RED evidence is `runtime-browser-focused-2.log` outside Git. This additional correction is included in the same Gate 3/final candidate before implementation.

### Continuation evidence and authorship

Production was accessed read-only through the owner-supplied SSH/WSL route. The October 1 capture confirmed all 996 application/config/public-runtime files, all guide files, and live Caddyfile. The imported workbook PHP SHA-256 is `6103f6452e60481414e299a762d5af4e4dd707c8969187f0ec56727023f807d9`. After import, the only deliberate application difference from production is removal of the redundant inline column widths in `_otiz-v2-group-table.php`; existing external CSS already owns those exact widths. No production database or container was changed.

Root authored the contract and regression/test corrections, incorporating a read-only fixture diagnosis from `/root/ci_inventory`. The same separate sol/low agent implemented the scanner correction, exact workbook import, and view-only CSP correction. `/root/pr286_gate3` independently reviewed the tests and owns the final implementation review; it authored neither. Two initial sol task starts failed for model capacity; the owner declined using another model, and all dispatched author/reviewer work remained sol/low.

Full failed-job logs and all 17 regression failures from CI run `36391356465` were collected and inspected before corrections. Root then corrected stale labels, asset hashes, role fixtures, dashboard selectors, positive calculation fixtures and deterministic evidence timestamps. No expected payment amount, historical claim, authorization denial or no-write assertion was removed. A real CSP violation and a root-detected multiline-SQL false negative were resolved under the same independent gates. Earlier approvals are retained as superseded history; a test approval never represented approval of an open implementation defect.

Bounded local checks cover all repaired failure groups, the full production-runtime browser journey, the new SQL detector fixtures and the existing 59 architecture unit tests. The browser image lacks `ZipArchive` and `rg`, so those focused checks used host PHP with isolated copied dependencies and a disposable test-only MariaDB service. Source-bound harness evidence was refreshed when later regression additions invalidated earlier records; stale records remain retained as UNKNOWN. No full local `make test`/`make verify` was run. All raw logs, original source payloads and snapshots remain outside Git under `/Users/antropophag/.local/share/fmonitor-2/pr286-20261001/`.

Publication/merge evidence is the exact-head check history and merge state of [PR #286](https://github.com/Antropophag/fmonitor-2/pull/286), rather than a claim that the pre-publication local checks alone establish CI GREEN. This continuation authorizes merge after required reviews and exact-source CI; it does not authorize a production deployment. Original WIP #257 and the separate Docker-growth checkout remain preserved.
