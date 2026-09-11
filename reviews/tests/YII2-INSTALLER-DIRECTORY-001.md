# Test review: YII2-INSTALLER-DIRECTORY-001

- Reviewer: `/root/installer_gate3`
- Test author: `/root`
- Reviewed source: base `aa4d20f30edb4b5e9c7de7abad8ac2d016ec4952` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T202807Z-4ed5bbdebc/snapshot/source.patch`, patch SHA-256 `027352266e8f9e1b8a0ce9e0aa832c819b705c1cc8a669f4a322462a4c180953`, source digest `168218aa23af524c7217fba61b68821ddae666b06b3d83731b6f2cb37e2352d4`
- Agreed review scope / prior findings disposition (for rereview): initial Gate 3 review of A1-A7, OpenSpec, verification plan, both root-authored tests and retained intended RED evidence; no prior findings
- Specification: `specs/YII2-INSTALLER-DIRECTORY-001.md`
- Public seam: `GET|HEAD /pilot/installers` through real Yii HTTP and browser
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T202807Z-4ed5bbdebc/package.json`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T202807Z-4ed5bbdebc/verification-plan.json`, SHA-256 `d83dfb97a04f77957b84a6a9efdf8e5aafc967c21bb2db6f1047146e8aced7f3`
- Red commands and retained evidence: `php tests/Yii2/yii2_installer_directory_http_001_test.php` -> intended route-absent 404, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789072053514264000-5395294cd9e14fabae6696bb161ecba3.json`; `php tests/Yii2/yii2_installer_directory_browser_001_test.php` -> intended route-absent 404, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789072062305996000-69a97f4bd708434491a68a6cf6c94e65.json`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **HIGH — A3/A4 assignment semantics are largely untested.** Locations: `specs/YII2-INSTALLER-DIRECTORY-001.md:50-70`, `tests/Yii2/InstallerDirectoryFixture.php:12-18`, `tests/Yii2/yii2_installer_directory_http_001_test.php:9-11`. The fixture contains only one registered version and one active assignment per case. It does not distinguish latest-version selection, prepared/superseded orders, release rows, future/expired intervals, multiple assignments, or stable assignment ordering. `availability=assigned` is not tested. A plausible implementation using any registered version or ignoring validity/release would pass. Correction: add independently constructed cases covering all inclusion/exclusion rules, latest-version replacement, multiple ordered assignments, and both `assigned` and `free`.

2. **HIGH — A1/A2/A5 rejection and failure matrix is incomplete.** Locations: specification lines 17-29, 31-44 and 72-79; HTTP test lines 8-16. Missing observable checks include safe anonymous return URL; HEAD parity of significant security/cache headers; malformed query encoding; valid canonical page above calculated page count; missing schema/table/column, invalid DB/prefix/config and query/read failure; failure-path byte-equivalent facts; and absence of DB result reads for rejected query shape. Only one invalid-row 503 is exercised. Correction: add focused fault/config/schema cases with sanitized-body assertions and before/after fact snapshots; add explicit header parity, malformed encoding, and `page=4` for the three-page fixture.

3. **HIGH — A2/A3 expected-value sensitivity is insufficient.** Locations: specification lines 31-58; HTTP test lines 9-11. The tests do not verify working/dismissed/assigned/max-update summary values, `pages=3`, numeric tab-ID search normalization, exact status filtering, zero filtered-result state, or deterministic `fio` then numeric-tab sorting. Broad substring checks such as `40 сотрудников` and filter names can pass with materially wrong projections. Correction: assert independent literal values for every summary field, ordered row identities, numeric-search examples including leading zeros/non-digits, separate status filters, and the distinct zero-results state.

4. **HIGH — A6 browser contract is only partially exercised.** Locations: specification lines 81-92; `tests/Yii2/installer_directory_browser.mjs:2`. The browser test omits current “Монтажники” navigation state, identity/logout, permission-filtered navigation, status/availability control accessibility, reset behavior, narrow-row content, dismissal/source dates, refresh, logout, and keyboard operation. Its generic width calculation does not establish those requirements. Correction: add browser assertions/actions for each listed behavior, using at least two roles where permission-sensitive navigation is required.

5. **HIGH — A7 bounded-query and runtime-closure evidence is not demonstrated by the reviewed tests.** Locations: specification lines 94-107; HTTP test line 17; verification-plan boundaries and commands. The visible test calls `$h->noLegacy()` only after requests, but provides no observable query-count/N+1 assertion and no route-specific transitive load trace proving controller/query/view did not runtime-read or load `rapid-pilot`. The planned architecture command is future Gate-4 evidence, not Gate-2 sensitivity. Correction: add a bounded query-count assertion for the 125-row/multi-assignment case and a route-specific production load trace or equivalent executable boundary assertion.

6. **HIGH — Fixture depends on wall-clock date and will expire.** Locations: specification lines 54-57; fixture lines 14-18. Assignments are hard-coded valid through `2026-12-01`, while behavior uses the current Europe/Moscow date. The expected count of 40 becomes wrong after that date, so the suite is not deterministic. Correction: control the application clock or construct validity dates relative to an injected fixed Moscow date.

7. **MEDIUM — Several mandatory row-integrity cases are absent.** Locations: specification lines 33-39 and 68-70; HTTP test lines 15-16. Only invalid employment status is tested. Missing duplicate/nonpositive identities, empty/whitespace required strings, invalid dates/timestamps, invalid optional dismissal date, and broken joins allow partial-catalog regressions to escape. Correction: add representative cases for each validation family and assert whole-response sanitized 503, never partial data.

Confirmed without findings: both retained RED runs match the exact source and fail for the intended missing Yii route, not setup failure; OpenSpec, normative spec, verification input and generated plan are coherently linked; both tests are registered once in `suites.tsv` and `categories.json`; the plan carries all four required categories and nine commands; patch and active/package plan digests match. CI and deployment remain `UNKNOWN`, not approval or GREEN.

## Required changes

Correct all seven findings and submit the corrected exact-source delta for independent Gate 3 rereview before implementation.

---

## Gate 3 rereview v2 — 2026-09-10

- Reviewer: `/root/installer_gate3`
- Test author: `/root`
- Reviewed source: base `aa4d20f30edb4b5e9c7de7abad8ac2d016ec4952` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T203459Z-804a899223/snapshot/source.patch`, patch SHA-256 `8cca8636a549d77b0362c1c40b7726d07ca3aab70032aa6a7208920a8df92fd7`, source digest `c9a33a736845adff0e333ae65b430edc20fe82552e7e6dc63934bbfa549201ef`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T203459Z-804a899223/package.json`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T203459Z-804a899223/verification-plan.json`, SHA-256 `7c0fc7be4dd535f3db91d9cbd4c3f1d8cee679d9ab921e4c4de5e69f8579c8b1`
- Red evidence: HTTP record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789072466940501000-49d300248b074d659a2e40cfae0b0c51.json`; browser record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789072473444281000-7b2548d39c6d47c5924834fcb67d5d65.json`; both exact-source `INTENDED_RED` on absent Yii route
- Verdict: `CHANGES_REQUESTED`

### Findings

1. **HIGH — Finding 5 remains unresolved and the correction weakens the accepted contract.** Locations: `specs/YII2-INSTALLER-DIRECTORY-001.md:60-70,94-107`, `openspec/changes/yii2-installer-directory/design.md` Risks / Trade-offs, and the HTTP test final assertion. The correction deleted “Один read не выполняет per-row DB query” from A4 instead of adding sensitivity. A7 still requires bounded query behavior, while OpenSpec still requires a bounded two-stage query and query-count/no-per-row verification. The test has no observable query-count assertion, and `$h->noLegacy()` does not visibly establish the route-specific production transitive-load trace. Correction: restore or clarify the bounded-query requirement, add an executable upper-bound/N+1 assertion for the large multi-assignment fixture, and make route-specific rapid-pilot load closure observable.

2. **HIGH — Finding 7 remains partially unresolved.** Locations: specification lines 68-70; HTTP invalid-row loop and missing-table case. Empty name, invalid status/timestamp/date and missing table are covered, but duplicate/nonpositive identities and broken assignment/object join integrity remain untested. A partial-catalog implementation can still pass. Correction: add representative duplicate/nonpositive identity and broken-join fixtures, asserting whole-response sanitized 503 and no partial catalogue.

3. **MEDIUM — Finding 1 is mostly corrected, but stable assignment ordering remains insensitive.** Locations: fixture assignment cases and HTTP first-page literal assertions. Latest prepared version, release, future, expired, multiple active assignments and both availability values are exercised. The test only checks that `ORDER-5001-1` and `ORDER-5010-1` occur somewhere; reversing their required object-ID order still passes. Correction: compare their DOM/order positions or assert the ordered assignment-link sequence.

4. **MEDIUM — Finding 2 is only partially corrected for A5's enumerated failures.** Locations: specification lines 72-79; HTTP missing-table block. Safe return, header parity, malformed query, out-of-range page, missing table, failure no-writes, and invalid-shape-before-DB behavior are covered. Invalid configured DB/prefix/configuration and a direct query/read failure remain unexercised although A5 explicitly makes each a sanitized 503 case. Correction: add injectable configuration/connection/query faults or narrow the normative failure taxonomy with an explicit reviewed rationale.

5. **MEDIUM — Finding 3 is corrected behaviorally, but summary assertions are not structurally sensitive.** Locations: HTTP first-page literal loop. Ordering, page bounds, numeric search, status/availability filters and distinct zero results are covered. Expected summary values `84`, `41`, and `5` are unrestricted body substrings and can occur in row IDs, dates, links or other content even when summary computation is wrong. Correction: select labelled summary fields in the DOM and assert exact values.

6. **MEDIUM — Finding 4 remains partially unresolved.** Locations: specification lines 81-92 and `tests/Yii2/installer_directory_browser.mjs`. Current section, identity/logout, labels, reset, refresh, keyboard search, narrow viewport and row content were added. The test does not prove “only permitted navigation items”, because it uses one role and asserts no forbidden-item absence. It also does not explicitly exercise keyboard accessibility of links/pagination. Correction: test permission-sensitive navigation with independently known allowed/forbidden items and operate at least pagination/object navigation by keyboard.

Disposition: prior finding 1 partially corrected; 2 partially corrected; 3 mostly corrected with summary sensitivity remaining; 4 partially corrected; 5 unresolved with inconsistent contract weakening; 6 corrected by validity through 2099; 7 partially corrected. Both new retained RED runs match the exact source and fail for the intended absent Yii route rather than setup. Plan/package/snapshot bindings are coherent; inventory registration remains closed and complete. CI and deployment remain `UNKNOWN`, not approval or GREEN.

### Required changes

Correct all six current findings and submit a new exact-source package for independent Gate 3 rereview before implementation.

---

## Gate 3 rereview v3 — 2026-09-10

- Reviewer: `/root/installer_gate3`
- Test author: `/root`
- Reviewed source: base `aa4d20f30edb4b5e9c7de7abad8ac2d016ec4952` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T204032Z-0ca3b286e1/snapshot/source.patch`, patch SHA-256 `f97e9c109083af1e0fa04bc34e81fdcbcb0b4cc45379179387b7bef17e264390`, source digest `9da3996e22098ce7de966e5cb4796b078a92bff878843165dc3f98a72ec186bd`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T204032Z-0ca3b286e1/package.json`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T204032Z-0ca3b286e1/verification-plan.json`, SHA-256 `761d736a2474bc62112bf4105c32c04bf92be815cff65c982ba8b3d247dc1093`
- Red evidence: HTTP record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789072810340875000-0867bdbcdfff441e97e626e0f853d206.json`; browser record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789072817291256000-374db30076d44ed0b0f89720a06bea6f.json`; both exact-source `INTENDED_RED` on absent Yii route
- Verdict: `CHANGES_REQUESTED`

### Findings

1. **HIGH — The bounded-query assertion bypasses the declared public seam and fixes an implementation API before Gate 4.** Locations: `specs/YII2-INSTALLER-DIRECTORY-001.md:3-5,63-68,94-105`, `tests/Yii2/yii2_installer_directory_http_001_test.php:4,21`, and the verification-input seam. The contract and verification input define the seam as real `GET|HEAD /pilot/installers`, but the query-count check directly constructs `FMonitor2\Workforce\MariaDbYiiInstallerDirectory` and invokes its planned `read('2026-09-10', ['page'=>1])` method. This is an implementation-side seam and dictates the class, constructor and method signature before implementation. It measures a second synthetic DAO call rather than the HTTP request whose bounded behavior A7 promises. An HTTP implementation could perform N+1 queries and still pass if the directly invoked object does not. Correction: instrument and count DB queries made by the real authenticated HTTP request, using the fixture/runtime connection or route trace, and assert the `<=4` bound there. Do not directly instantiate or call the planned production read-model API.

All earlier substantive gaps are otherwise disposed: assignment inclusion/exclusion and stable order are sensitive; summary values are DOM-scoped; safe return, HEAD headers, closed query shape, page bounds, connection/prefix/schema failures and no-write behavior are covered; runtime load closure is route-specific; browser permissions and keyboard navigation are exercised; canonical schema ownership explicitly resolves duplicate/FK cases; wall-clock expiry is removed; inventory and verification-plan closure remain coherent. Both retained RED runs match source `9da3996e22098ce7de966e5cb4796b078a92bff878843165dc3f98a72ec186bd` and fail for the intended absent route rather than setup. CI and deployment remain `UNKNOWN`, not approval or GREEN.

### Required changes

Move bounded-query observation to the declared real HTTP seam and submit a new exact-source package for independent Gate 3 rereview before implementation.

---

## Gate 3 rereview v4 — 2026-09-10

- Reviewer: `/root/installer_gate3`
- Test author: `/root`
- Reviewed source: base `aa4d20f30edb4b5e9c7de7abad8ac2d016ec4952` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T204412Z-a960f9d6ec/snapshot/source.patch`, patch SHA-256 `2a286bdcf05b3b56ffcf76dcf34c91441f34a8461de56a22847079c8cd94ac33`, source digest `df0220c226587c66fab81c2c033a05f603d580066fe952c6cd3fe1017efc4468`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T204412Z-a960f9d6ec/package.json`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T204412Z-a960f9d6ec/verification-plan.json`, SHA-256 `0a5d322d3095c290e7852dd7e40fa7d41d4026b466a6fd7cf7d2b8f3b451c2a9`
- Red evidence: HTTP record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789073031430551000-1b8f55798b7b4d839743cf305ac6b2f7.json`; browser record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789073037065844000-33b602006b664238a15124b41b91c8a8.json`; both exact-source `INTENDED_RED` on absent Yii route
- Verdict: `CHANGES_REQUESTED`

### Findings

1. **HIGH — HTTP query instrumentation can pass without observing any query.** Location: `tests/Yii2/InstallerDirectoryFixture.php`, `routeNoLegacy()`. The correction instruments the real HTTP runtime and no longer fixes the production DAO API, resolving v3's seam problem. It only asserts `$r['queries'] <= 4`, so a zero count passes. Broken instrumentation, a query path bypassing `DirectoryCountingCommand`, or a hard-coded/non-DB projection can satisfy the test although bounded SQL behavior was never observed. The method also asserts only that some installer trace exists, not that a matching successful unfiltered GET entry was found and checked. Correction: track a separate `boundedGetFound` flag for `GET /pilot/installers` with status 200, require it to be true, and assert the observed count is both positive and at most four (`1 <= queries <= 4`). An exact expected count is also acceptable if intended by the contract.

All earlier findings are otherwise resolved across the complete candidate: query observation is attached to real HTTP; route-specific transitive load closure is captured; assignment validity/latest-version/exclusion/order sensitivity is present; summary assertions are structurally scoped; rejections, failures, authorization, return path, HEAD, privacy and no-write cases are covered; browser navigation permissions, keyboard flows, responsive behavior and logout are exercised; determinism, canonical schema responsibility, inventory and plan closure are coherent. Both evidence records match source `df0220c226587c66fab81c2c033a05f603d580066fe952c6cd3fe1017efc4468` and retain the intended missing-route RED. CI and deployment remain `UNKNOWN`, not approval or GREEN.

### Required changes

Require a matching successful unfiltered GET trace and a positive observed query count bounded by four, then submit a new exact-source package for independent Gate 3 rereview.

---

## Gate 3 rereview v5 — 2026-09-10

- Reviewer: `/root/installer_gate3`
- Test author: `/root`
- Reviewed source: base `aa4d20f30edb4b5e9c7de7abad8ac2d016ec4952` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T204608Z-dc50eccc7a/snapshot/source.patch`, patch SHA-256 `413bf9c3136ca854dbbc6381b5624a9149fad7e6baa28f09522a241f092c93c7`, source digest `6bcabe783fe105cad70cf5fe7d880772d14887369564468f4e064dbcde10f87e`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T204608Z-dc50eccc7a/package.json`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T204608Z-dc50eccc7a/verification-plan.json`, SHA-256 `7d6575318bbddd2ba862fa8fa9b72eb5ee080d3d4a03303951aa653c778911ff`
- Red evidence: `php tests/Yii2/yii2_installer_directory_http_001_test.php` -> exact-source intended missing-route 404, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789073147981515000-579000723b554ad5957c3b36ca30ee4e.json`; `php tests/Yii2/yii2_installer_directory_browser_001_test.php` -> exact-source intended missing-route 404, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789073153692229000-9cba4c18bc6044d8902676543a3cecb7.json`
- Prior findings disposition: v4 query instrumentation now requires a matching successful unfiltered `GET /pilot/installers`, observes a positive query count and enforces the normative maximum of four on that public HTTP request; all earlier v1-v3 findings remain resolved
- Verdict: `APPROVED`

### Findings

None. The complete A1-A7 candidate was rechecked for traceability, public seam, sensitivity, independently derived expectations, rejected cases, authorization, failure privacy, read-only facts, assignment/version/date semantics, deterministic ordering and clock bounds, browser/accessibility behavior, route-specific runtime closure, bounded HTTP query behavior, inventory registration and generated-plan closure.

### Required changes

None. Gate 3 is approved for exact source `6bcabe783fe105cad70cf5fe7d880772d14887369564468f4e064dbcde10f87e`. CI and deployment remain `UNKNOWN`; this approval advances the slice to implementation and does not imply Gate 5, CI, merge, deployment or stand cutover.

---

## Test-helper delta review — 2026-09-10

- Reviewer: `/root/installer_gate3`
- Approved baseline: package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T204608Z-dc50eccc7a/package.json`, source `6bcabe783fe105cad70cf5fe7d880772d14887369564468f4e064dbcde10f87e`, snapshot patch SHA-256 `413bf9c3136ca854dbbc6381b5624a9149fad7e6baa28f09522a241f092c93c7`
- Reviewed live delta only: `tests/Yii2/yii2_installer_directory_http_001_test.php`, SHA-256 `91f3b03f7ecf4d00e975d8117f0792fa30d684b91cc3fee80d9cd403203269eb`; `tests/Yii2/InstallerDirectoryFixture.php`, SHA-256 `4db7759d7cdcf10f449e07f70b7f53f11d70ffb36f723bd2a3786f9908d25d55`
- Excluded scope: all production/executor WIP; no production approval is expressed or implied
- Verdict: `TEST_HELPER_DELTA APPROVED`

### Findings

None. Encoding the no-result Unicode needle with `rawurlencode()` preserves the normative search and distinct-empty-result expectation while making the HTTP request valid. Query instrumentation remains attached to the real authenticated HTTP seam and still requires a matching successful unfiltered GET with a positive count bounded by four. Restricting the counter to SQL containing `fm2_workforce_catalog` or `fm2_order_installers` removes unrelated authorization/session queries without weakening the workforce projection budget: summary/count/catalog SQL is anchored by the workforce table and assignment projection SQL by the order-installers table.

### Required changes

None for this bounded test-helper delta. The prior Gate 3 approval remains applicable to these corrected test-helper bytes only; Gate 4 production and Gate 5 remain separately unreviewed.

---

## Test-helper denial-actor delta review — 2026-09-10

- Reviewer: `/root/installer_gate3`
- Reviewed delta: `tests/Yii2/yii2_installer_directory_http_001_test.php` denial actor `95` -> `96`
- Reviewed helper SHA-256: `a181c2de205124e9ddccfc910ef48a22aa41d11192886138825eeda884117332`
- Supporting fixture SHA-256: `4db7759d7cdcf10f449e07f70b7f53f11d70ffb36f723bd2a3786f9908d25d55`
- Excluded scope: all production/executor WIP; no production approval is expressed or implied
- Verdict: `TEST_HELPER_DELTA APPROVED`

### Findings

None. `PreopeningFixture` independently binds actor 95 to role 5 and actor 96 to role 6. `InstallerDirectoryFixture` deliberately grants `installers.read` to role 5 for the permission-sensitive browser reader, while role 6 retains neighboring `objects.read`, `assignment_order.original.read`, and `otiz.manage` permissions but receives no `installers.read`. Using actor 96 therefore preserves a strong exact-capability denial: the actor is authenticated and authorized for adjacent capabilities yet must receive `403` for `/pilot/installers`. The accepted authorization expectation is not weakened, and the allowed browser actor and denied HTTP actor are distinct.

### Required changes

None for this bounded test-helper delta. Gate 4 production and Gate 5 remain separately unreviewed.

---

## Test-helper pagination delta review — 2026-09-11

- Reviewer: `/root/installer_gate3`
- Reviewed delta: scoped pagination/sidebar `aria-current` assertions in `tests/Yii2/yii2_installer_directory_http_001_test.php` and scoped page-1/page-2 pagination assertions in `tests/Yii2/installer_directory_browser.mjs`
- Reviewed file SHA-256: HTTP `ec0261403612fc73c659e665ad516453c08f13f5a11efd7ac00839d60a5b6b07`; browser `b5cd8e440d53be875ea333ab7be7b06cf40f080086c7299a93dedbbce56ca3ee`
- Excluded scope: all production/executor WIP and delivery documentation; no Gate 5 production approval is expressed or implied
- Verdict: `TEST_HELPER_DELTA APPROVED`

### Findings

None. A2 requires pagination, when present, to contain exactly one `aria-current="page"`. The HTTP test now scopes that count to `nav.fm2-pagination` and independently proves that the directory sidebar retains its own current marker. The browser test independently observes one current pagination link on page 1 and the current page-2 link after real keyboard navigation. These scoped assertions make a missing, duplicate, or stale pagination marker observable even though the earlier broad whole-document assertion remains.

### Required changes

None for this bounded test-helper delta. Production WIP and the delivery record remain excluded and require their own Gate 5 review.

---

## Test-helper obsolete-global-current delta review — 2026-09-11

- Reviewer: `/root/installer_gate3`
- Reviewed delta: removal of the obsolete whole-document `aria-current="page"` count from `tests/Yii2/yii2_installer_directory_http_001_test.php`
- Reviewed HTTP helper SHA-256: `c83110775f48cb58bcb920de38862950730070e7bdc00501c0ed4dc83ab2f604`
- Supporting unchanged browser helper SHA-256: `b5cd8e440d53be875ea333ab7be7b06cf40f080086c7299a93dedbbce56ca3ee`
- Excluded scope: all production/executor WIP and delivery documentation; no Gate 5 production approval is expressed or implied
- Verdict: `TEST_HELPER_DELTA APPROVED`

### Findings

None. A2 requires exactly one current marker within pagination, while A6 independently requires the directory item to be current in the primary sidebar. The retained HTTP assertions scope each requirement to its own navigation container, and the retained browser assertions verify sidebar state plus pagination state across pages 1 and 2. Removing the obsolete whole-document count eliminates the false assumption that both navigation regions together may contain only one current marker; neither normative expectation nor regression sensitivity is weakened.

### Required changes

None for this bounded test-helper delta. Production WIP and delivery documentation remain excluded and require separate Gate 5 review.

---

## Test-helper impossible-status delta review — 2026-09-10

- Reviewer: `/root/installer_gate3`
- Reviewed delta: removal of the fixture-side `UPDATE employment_status='unknown'` case from `tests/Yii2/yii2_installer_directory_http_001_test.php`
- Reviewed helper SHA-256: `88e1aba307fec33dd091328463aaeda9bfcba040129c0212dc99865526653e2d`
- Excluded scope: all production/executor WIP; no production approval is expressed or implied
- Verdict: `TEST_HELPER_DELTA APPROVED`

### Findings

None. Canonical workforce schema enforces `CHECK (employment_status IN ('employed','dismissed'))`, so the removed fixture mutation is rejected before the public HTTP seam and cannot prove A5 response behavior. Removing it avoids a setup-side false failure without weakening any reachable observable expectation. The candidate retains HTTP-visible invalid required-name, timestamp and date projections plus missing schema, invalid prefix and unavailable configured DB cases. The normative spec separately assigns duplicate identities and broken foreign-key joins to canonical schema enforcement/schema-drift handling.

### Required changes

None for this bounded test-helper delta. Gate 4 production and Gate 5 remain separately unreviewed.
