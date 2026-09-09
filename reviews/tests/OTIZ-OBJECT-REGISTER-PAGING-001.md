# OTIZ-OBJECT-REGISTER-PAGING-001 — Gate 1 review

Reviewer: independent specification reviewer (did not author the specification,
OpenSpec change, tests, or implementation)

Baseline inspected: `a9810b80f39eca6da9d849eca62e940f50135373`

Verdict: **APPROVED**

## Reviewed artifacts

- `specs/OTIZ-OBJECT-REGISTER-PAGING-001.md` —
  `244b34b2f7a836c391359499612109790ac575aa3fcfd5342a57f9df4a227825`
- `openspec/changes/paginate-otiz-object-register/proposal.md` —
  `46f59d08586937abe2d07dcdcb2ba41bace24b680ae4243b0649e70da549d71b`
- `openspec/changes/paginate-otiz-object-register/design.md` —
  `98c015f6e54168d90be476ab0ae661a5528fb2ad235104fc282ea5fd8b03afa5`
- `openspec/changes/paginate-otiz-object-register/specs/otiz/object-register-pagination/spec.md` —
  `260babaa468732cbf81174c5869360ab0095ff9db2694478c9792e36f7dde375`
- `openspec/changes/paginate-otiz-object-register/tasks.md` —
  `58216f82c03a63d28da66eef09d42c23190239c22a1ebedbaacc758759656db3`

## Gate 1 assessment

The specification defines an observable public read seam, response shape, stable
domain errors, authorization order, and a complete query representation. Known
parameters have deterministic PHP types and textual forms, defaults and bounds;
unknown GET tracking keys are deliberately ignored. Empty and out-of-range pages
have distinct outcomes.

The server-side contract applies literal search, state selection, and a fully
specified stable ordering before SQL `LIMIT/OFFSET`. The default order preserves
the current oracle tuple, including rank-zero null/unknown calculation states and
an `object_id` tiebreaker. Page hydration is bounded, current progress is limited
to page objects, and DB query count is constant with respect to object count and
page size. The separate global fold may scan O(N) rows but must remain unbuffered
and must not build a full object array.

The money and state contract preserves the current oracle rather than introducing
a new formula. It fixes the latest-snapshot order to report date then id, retains
all closure/reversal facts, gives `missing_norm` the declared precedence, and
keeps both historical penalty meanings explicit. In particular, global balance
is the sum of per-object clamped balances using stored deadline closure, while a
row balance uses traced deadline penalty. The independent overclosure example is
sensitive to an incorrect aggregate-level clamp. NativePremiumNorms remains the
single owner of premium and shaft mappings, with planned PHP/SQL boundary parity
coverage.

The HTTP behavior preserves the existing OTIZ navigation and read-only role,
uses GET links/forms with page reset on filter changes, and states keyboard,
narrow-screen, empty-result, and no-bulk-action behavior. Unauthorized and
inactive actors are rejected before registry reads. The contract prohibits fact,
schema, and persistent-projection writes.

The 30,000-object plan measures first/last pages, search and state filtering,
response size, query count, memory behavior, and query plans in an isolated
contour. Wall-clock data is evidence rather than a flaky acceptance threshold.
This is sufficient to evaluate the bounded page and bounded-memory summary design
without changing schema or weakening money, history, or authorization assertions.

The normative Acceptance section and the OpenSpec delta requirement body compare
exactly. Proposal, design, tasks, and specification agree on scope and sequencing.
`openspec validate paginate-otiz-object-register --strict` passes.

## Findings

No open Gate 1 findings. Earlier review findings about per-object balance clamping,
the literal default ordering, bounded query behavior, and exact query parameter
types/error precedence were corrected before this approval.

This first verdict approves Gate 1 only. The separately scoped Gate 3 module-test
verdict below does not broaden that specification verdict.

## Gate 3 module-test review

Reviewed test artifacts:

- `tests/Otiz/object_register_paging_001_test.php` —
  `dc9a3c9fb13c5cf210dcba9900a362c27135b28187852438eee4e77fd502be1f`
- `tests/Support/ObjectRegisterPagingFixture.php` —
  `91abcd0fa1088cb5fa1bd4adcc5b5fb495fb23fa747e34b53529e61653e385af`
- RED evidence `/tmp/fm2-page17-red-module.log` —
  `c444e84cf64a66fd1c853ce5ddeae74ba790ef789d9920408ec460b21e289a5b`

Verdict for these two test artifacts: **APPROVED**

The test calls the specified `ObjectRegister::read()` public seam and has no
fixture alias or substitute implementation for that seam. The retained RED fails
immediately and causally because `FMonitor2\Otiz\ObjectRegister` does not exist;
fixture setup is not the failure reason.

The test now loads the canonical fixture runtime before constructing the fixture
and checks absence/presence of the public seam only after valid setup. A standalone
ordinary invocation, without `auto_prepend_file`, passes in
`/tmp/fm2-page17-green-module-final.log` (SHA-256
`29fe59b59624671366ba5be1fc568aa2c1af9bd0cd8364aaa4da2f755b83bf86`).
This setup-only correction supersedes the earlier test hash; expectations and the
independently reviewed fixture are unchanged.

The fixture and expectations independently exercise bounded 50/50/25 paging,
page-size changes, whole-register literal search, wildcard escaping, empty page
one, stable ties, and the complete default state/snapshot/id order. State tests
cover completed closure, partial closure, blocked, no-new-amount, missing-norm
precedence, and report-date-before-id snapshot selection. Query rejection covers
representative type, canonical representation, range, allowlist, length, page
bound, and authorization-first cases without expanding into an unnecessary rare
input catalogue.

Financial expectations are fixed numeric values derived from the specification.
They verify global results across pages and filters, per-object overclosure
clamping, all closure facts in paid totals, and the preserved distinction between
stored deadline closure in global balance and traced deadline penalty in row and
penalty output. Card-boundary cases exercise integer parsing, Cyrillic
normalization, cargo/passenger handling, unsupported norms, shaft material, and
the missing-type passenger fallback, making a duplicated or drifting SQL mapping
detectable.

The before/after fingerprint includes every fixture-prefixed table, its schema
definition from `SHOW CREATE TABLE`, unordered row content, and row count. It detects
business writes, request-time DDL, and a newly persisted projection across both
accepted and rejected reads. The fixture is disposable and uses canonical
migrations.

This bounded approval covers only the module test and support fixture at the
hashes above. The HTTP/browser/30,000-object evidence is deliberately separate;
it must retain its own causal no-alias RED and independent review. No production
code, HTTP test, implementation, GREEN result, Gate 5, or delivery claim is
approved here.

## Gate 3 HTTP-test review

Reviewed HTTP test artifact:

- `tests/Otiz/object_register_paging_http_001_test.php` —
  `48c45bc54af45d0d3fec463146aee8bd7c4099977da8ea5c4280bd7511bff4dd`
- Authoritative no-alias RED `/tmp/fm2-page17-red-http.log` —
  `48cbbe2e96e85de18c448366cf42023421fb293c5584a4e2f035815d6806752e`
- Separate diagnostic evidence `/tmp/fm2-page17-red-http-diagnostic-alias.log` —
  `dde48a265a4af88eff4d4f73e414fd0f0d5380411b79db5685215ec9773a92a2`

Verdict for the HTTP test artifact: **APPROVED**

The authoritative test runs the real router, session, login, and HTML seams. It
does not set `PHPRC`, install an auto-prepend file, or alias
`NativePremiumNorms`. Its baseline RED is the actual HTTP 500 caused by the
legacy route's unresolved global `NativePremiumNorms` reference, with the server
stack ending in `RapidPilotOtiz->objects()`. Replacing that route with the new
public seam must first restore HTTP 200, so the test cannot hide the inherited
autoload defect.

The separately retained diagnostic uses the existing compatibility alias only to
continue through that predecessor failure and expose the missing paging behavior.
It receives 125 object rows where the test requires exactly ids 1–50. This makes
the RED sensitive to the requested server-side bound as well as the real no-alias
integration path; the diagnostic alias is not part of the acceptance test.

After the initial response, assertions cover page-one DOM exclusion, accessible
current-page state, preserved sort/page-size links, the 25-row last page, and an
identical global summary across pages. A combined search/state/sort/page-size GET
finds the former third-page object and verifies normalized controls and page
reset. Empty results and representative invalid, out-of-range, and
authorization-first requests check the specified 200/400/404/403 mapping.

The fixture uses synthetic disposable data and the existing login/session route.
The expectations operate on rendered object links and controls rather than a
fixture-only response. This HTTP test complements, rather than duplicates, the
approved module test's exhaustive money, state, normalization, and read-only
fingerprints.

This approval is limited to the exact HTTP test hash above. The compatibility
diagnostic is evidence only and must not be installed in the authoritative test
or delivered runtime. Browser interaction, 30,000-object performance proof,
production implementation, GREEN, Gate 5, CI, and delivery remain unapproved.
