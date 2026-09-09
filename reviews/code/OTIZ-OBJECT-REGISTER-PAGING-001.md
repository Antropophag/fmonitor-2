# OTIZ-OBJECT-REGISTER-PAGING-001 — bounded Gate 5 review

Reviewer: independent code-review agent (did not author the specification, tests,
or implementation)

Comparison baseline: `a9810b80f39eca6da9d849eca62e940f50135373`

Verdict: **APPROVED** for the bounded production diff below

## Reviewed production artifacts

- `app/Otiz/ObjectRegister.php` — `6329f17d3cdda47dbab2400f323e5990aa1905df794ea3a58e78ed4384cbf6b1`
- `app/Otiz/MariaDbObjectRegister.php` — `dbed3e603c20dcc31edcd1839bb5833204df88facafb60dce2564dc563f8335e`
- `app/Otiz/MariaDbObjectRegisterQuery.php` — `6dccca2ecfb35f304aaa9c0b25d5f61022a2a9a3b71ae39e0004c9b539f2a976`
- `app/Otiz/ObjectEconomy.php` — `c1efff366f9c5d661c5788f60233d168e3416725b3505d03243b676a2d292e72`
- `app/Otiz/NativePremiumNorms.php` — `2bd20f9c6c401e7e2af9f4f0cabe600f4fa56f1478ce1af9fa925595d66f8429`
- `app/InspectionEvidence/MariaDbObjectCurrentProgress.php` — `03bbddf5d04521b718a69af12913cc057a69e80dc9cafb28845766483e179306`
- `app/PilotHttp/MariaDbOtizCurrentProgress.php` — `10d95882f10ce10c54df9bac23be88e849e9d502a85c5d386612ce53b5730b4d`
- `rapid-pilot/Otiz.php` — `b8fb7e61435d41f478def39ac7abe91164553ccbabdc993418bfecf88e0b5548`
- `rapid-pilot/otiz.js` — `5e3a7794b4bf104c1fa8f66c22b787001ad8a5cffe12ca8d5b3cb6afd3af6dbc`
- `rapid-pilot/pilot.css` — `65ee7eb12ba1d4db86f5233a9ffd0ea513c5486e2fc89d4e2baba97dfd3bdc57`
- `tools/benchmarks/otiz-object-register.php` — `73de355aee1866f1aa55e2f05cc35683cc45794804a935f184e77686af9b6047`

## Assessment

`ObjectRegister` is the explicit public read seam and centralizes normalization.
The adapter authorizes the active actor before query validation, validates table
prefixes, and performs count, page, page-only progress hydration, and the global
fold inside one read-only repeatable-read consistent snapshot. Failures roll the
transaction back. Search uses escaped literal data with `LOCATE`, state and sort
are allowlisted, and SQL order always ends in object id. `LIMIT/OFFSET` is applied
before row hydration.

The query count is constant with respect to object count and page size. The global
summary uses an unbuffered scalar result and performs no nested query or progress
lookup. It remains O(N) in time without retaining an O(N) object collection. No
DDL, write statement, temporary table, persistent projection, or request-owned
domain mutation was introduced.

The final SQL split keeps orchestration in the 71-line store and query construction
in the 105-line query module, clearing the hotspot boundary without changing the
public seam. For unfiltered/state-empty requests, count and raw ordering/search
operate on the one-row-per-object legacy source and apply the page limit before
norm joins. Derived-state filters still run against the complete economics set.
Their normal nonempty page obtains the filtered total from `COUNT(*) OVER()` in
the same result; an empty requested page performs an explicit fallback count so
page-one emptiness and out-of-range 404 remain distinct.
The maximum unsigned `LIMIT` in the operands CTE is a MariaDB materialization
barrier and cannot truncate the logical rowset. Page-only `payload_json` and
`inputs_json` are rejoined after the limit; the global fold reads only compact
input scalars and applies the original PHP `NativePremiumNorms` parser once per
row. The full global summary remains unchanged.

The latest snapshot window is ordered by report date and snapshot id. Closure
aggregates preserve all object history and the current-snapshot completion rule.
`ObjectEconomy` retains the established row/global distinction: traced deadline
penalty drives row display and aggregate penalties, while stored deadline closure
drives each independently clamped global balance term. The extracted premium
bands and shaft rules are generated from the same constants used by
`NativePremiumNorms`; the monetary table and formulas are not duplicated or
changed. Normalization parity is covered at integer, whitespace, Cyrillic,
material, lift-type, and unsupported-norm boundaries.

Page rows retain every column selected by the predecessor `objects()` query,
including `payload_json` and `inputs_json`, and add the specified derived fields.
An initial review finding identified those two omitted predecessor fields; both
were restored before this approval while the global summary kept its narrow
scalar projection.

Current-progress ownership moved without behavioral changes to
`InspectionEvidence`. The old `PilotHttp` name is a compatibility alias, so
existing public consumers continue to resolve while the new OTIZ module does not
depend on an HTTP namespace. Its focused predecessor regression passes.

`RapidPilotOtiz` now only maps the public result and stable domain errors into the
existing presentation. GET controls preserve search, state, sort, and page size;
filter submission omits page and therefore resets to page one. Pager links retain
context, rows are server bounded, empty results are visible without JavaScript,
and client-side filtering of the fully loaded DOM is removed. The CSS change only
allows the existing controls to wrap on narrow screens. The former inline-width
fund track is rendered as an SVG rectangle with a numeric width attribute and a
stylesheet-owned fill, preserving its geometry while satisfying the route CSP.

## Verification evidence

- Corrected standalone module test, with no auto-prepend flags:
  `/tmp/fm2-page17-green-final-plan.log` — PASS.
- Authoritative real-router HTTP test with no compatibility alias:
  `/tmp/fm2-page17-green-http.log` — PASS.
- Existing current-progress compatibility regression:
  `/tmp/fm2-page17-progress-regression.log` — PASS.
- Final `/tmp/fm2-page17-architecture-final2.log` — HTTP global-call
  qualification PASS; architecture check PASS, 7 rules. This supersedes an
  intermediate 151-line hotspot failure before query construction was extracted.
- PHP syntax checks for all new/changed PHP production files — PASS.
- `git diff --check a9810b80` — PASS.

The two PHP acceptance tests are registered once in the DB/integration inventory;
the browser launcher is registered once in E2E. The inventory update only adds
these explicit members and preserves prior baseline constants/assertions.
`verification_inventory_001_test.py` passes 15 tests. The CI composition update
adds the new fourth E2E member without removing a contract;
`verification_ci_001_test.py` passes 15 tests.

Headless browser evidence `/tmp/fm2-page17-browser-green.log` is PASS against the
real login/router UI. It verifies page 3 has 25 rows, keyboard selection,
whole-register search finding object 125, filter page reset, page-2 navigation,
mobile containment, and empty-state text. Console errors, CSP violations, page
errors, failed requests, and error responses are all empty. The reviewer visually
inspected the retained desktop and 390px mobile screenshots; controls, summary,
table containment, and pager remain usable. The browser fixture adds the ordinary
`objects.read` grant needed for its post-login landing route and does not bypass
the separately enforced `otiz.manage` check. Reviewed browser hashes are
`6b2c0394ba0d33f356872160f677984a55694e4bbbe16cafff169f003f6c2c89`
(Playwright test) and
`d830203ade3b986dc0a0bd91722846d5ac1208cbef415c0991691b9157af9312`
(launcher).

The final 30,000-object benchmark
`/tmp/fm2-page17-benchmark-final-with-baseline.json` is bound to the exact four
core hashes listed in its `sourceHashesStart/End`, with
`sourceStable=true`. First, last, search, and missing-norm state reads return
50/50/1/1 rows; raw session Questions deltas are 9/9/9/8 and PHP memory is fixed
at 8 MiB before, after, and at peak. Measured times are 1.37s, 1.78s, 1.33s,
and 5.19s respectively. The reproduced predecessor SQL returns all 30,000 rows,
raises PHP peak memory to 68 MiB, and takes 1.57s under the same fixture. This
evidence demonstrates the intended output/memory bound; it does not claim every
filtered query is faster than the predecessor full-list query. EXPLAIN plans and
hashes are retained. These are
measurements rather than unstable thresholds; the slower derived-state scan is
reported honestly. The evidence file SHA-256 is
`f18bb053d875fedbfd2f3c73ac6893b5bd4eb083cc5df3034bd8ac91fb2300c8`.

## Findings and boundary

No open findings in the reviewed production diff.

This approval does not claim full focused-suite completion, full CI, merge,
archive, deployment, or Done. Any production change from the hashes above
requires a fresh independent review.

## Superseding optimization addendum

The first bounded approval reviewed store hash
`193fd5177cb8bdf1d640ef718da69c6339e5772ef6d0b095b4a4b9bdbbeb4735`,
presentation hash
`2b15252caf1d7d3460af900d06d020f6223831e0ad4cb6cc847b64f4fd482ef5`,
and CSS hash
`dbbaea50cfa790a43637218c26de0c85fdd1fa76b7dceca63e39c8701ad33bd3`.
It found the missing predecessor `payload_json`/`inputs_json` page fields; those
were corrected before that verdict was issued. Subsequent 30,000-object evidence
showed bounded output and memory but avoidable repeated norm parsing. Intermediate
materialization and pre-limit candidates were reviewed as diagnostic steps and
were not final hashes. One intermediate 151-line store failed the hotspot ratchet;
that failure was preserved and corrected through the query-module split.

This addendum independently reviews and supersedes those production identities
with the final artifact hashes listed at the top of this record. The final
`dbed3e60...` store adds filtered `COUNT(*) OVER()` without changing selection:
a nonempty state page carries its full count in the window, while an empty page
falls back to the same full filtered count before deciding page-one success or
out-of-range failure. The page rows, totals, state precedence, global summary,
and independent result digests remain exact. No authorization, transaction,
money, history, or write behavior changed.

Final exact evidence:

- `/tmp/fm2-page17-green-window.log` — standalone module PASS at the final plan.
- `/tmp/fm2-page17-green-http-final.log` — real no-alias HTTP PASS.
- `/tmp/fm2-page17-architecture-final2.log` — HTTP qualification and all seven
  architecture rules PASS after the module split.
- `/tmp/fm2-page17-benchmark-final-with-baseline.json` — final four-core-file
  hashes stable start/end, 30,000 objects, bounded rows, fixed memory, constant
  query count, retained EXPLAIN plans, and the measurements recorded above.
- `/tmp/fm2-page17-browser-green.log` — final UI interaction and error inventory
  PASS; the presentation and CSS hashes match this review.

Addendum verdict: **APPROVED**, with no open findings. Full CI and integration
remain outside this bounded verdict.

## Final documentation receipt

The public performance report
`docs/operations/otiz-pagination-performance-2026-09-09.md` at SHA-256
`40957d8199de979e6b90777a07a766be66bcb43bf6eddb1ac3fff87387d3310f`
preserves all three optimization checkpoints, the frozen source hashes, both
4.061s and 5.186s state-filter observations, unchanged result digests, session
counter caveat, EXPLAIN identities, and the exact old SQL-only baseline. It
correctly distinguishes that SQL baseline from the prior 43.8 MB diagnostic HTTP
run and makes no SLA claim. The current combined raw evidence remains SHA-256
`f18bb053d875fedbfd2f3c73ac6893b5bd4eb083cc5df3034bd8ac91fb2300c8`;
the `40957d...` identity is the appended Markdown report, not a replacement raw
JSON hash.

The delivery checkpoint accurately marks focused implementation, browser,
architecture, performance, and independent Gate 5 work complete while leaving
tasks 4.3 (PR/full CI) and 4.4 (integration/Done/archive) unchecked. It states
that no user stand or volume changed. This docs-only receipt does not broaden the
bounded production approval or assert CI/Done.
