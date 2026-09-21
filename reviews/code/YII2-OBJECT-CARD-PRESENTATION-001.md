# YII2-OBJECT-CARD-PRESENTATION-001 — Gate 5 final review

- Date: `2026-09-21`
- Aggregating reviewer: independent agent `/root/final_object_card_review`
- Standards reviewer: independent agent `/root/final_object_card_review/standards_axis`
- Spec reviewer: independent agent `/root/final_object_card_review/spec_axis`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T222951Z-1830197aa8/package.json`
- Base: `f145e3e00f25644f5c4e32f7c2f3e8bba4f624a3`
- Exact candidate source: `a1b27c531e77c9c0c7791e7926c3fc83bb8d1cbc44e9c78fb53152a64d260322`
- Verdict: **CHANGES_REQUESTED**

The reviewers authored none of the specification, tests, production, or GREEN
evidence under review. This review record is the only Gate 5 edit.

## Blocking findings

### G5-1 — the card fabricates current installer employment status

`specs/YII2-OBJECT-CARD-PRESENTATION-001.md` A4 requires each installer to show
the current status «Работает» or «Уволен». The current projection does not supply
that fact. `app/PilotHttp/MariaDbAppliedObjectCardReader.php:21,35` constructs
selection installers with the constant snapshot value `status => 'employed'`
and no `employmentStatus`. `app/YiiRuntime/Views/object-card.php:14,36` reads the
absent `employmentStatus` and maps every value other than exactly `dismissed` or
`terminated` to «Работает». An installer dismissed after selection is therefore
shown as working.

This is not only a coverage gap: it is incorrect user-visible behavior and a
source-confusion defect between immutable selection membership and current
workforce status. Preserve the immutable selection snapshot, separately project
the current workforce status, and add a dismissed-current acceptance case.
`tests/Yii2/yii2_object_card_presentation_001_test.php:6,11` fixes only an
employed fixture, so the supplied focused GREEN cannot detect this regression.

### G5-2 — application code overrides the public `shlz-ui` tabs contract

`app/YiiRuntime/Assets/navigation.js:7-17` initializes the public
`enhanceTabs()` behavior and then installs app-wide click/keydown listeners that
remove `tabindex` from the selected tab. The public component explicitly owns
roving focus: `../shlz-ui/packages/behaviors/src/tabs.ts:61-77` assigns the
selected tab `tabIndex = 0` and all other tabs `-1`. The local shim mutates that
state after every public-controller transition and couples all Yii tabs to an
implementation-specific workaround.

`tests/Yii2/object_card_presentation_browser.mjs:2` locks in the override by
requiring `null` rather than the public controller's `"0"` for the active tab.
Remove the shim and assert the public component's `0/-1` behavior. If the public
contract itself must change, that belongs in `shlz-ui`, not in a consumer-side
post-processing layer.

### G5-3 — A3 is only partially implemented and untested

A3 requires thematic display of the already available planned, corrected, and
actual dates plus available readiness and equipment-delivery facts.
`app/YiiRuntime/Views/object-card.php:30-32` renders planned start/finish, actual
start, process/original state, and physical equipment characteristics. Corrected
deadlines are represented only by a link to certificates; no corrected dates are
shown, and no available readiness/delivery projection is rendered.

The focused test checks section labels but none of these required facts. Either
bind the existing available projections into the card or narrow the normative
contract through the gated specification process. The current implementation
does not satisfy the written A3 contract.

## Maintainability findings

### G5-4 — the main view and acceptance tests are excessively compressed

`app/YiiRuntime/Views/object-card.php:6-43` places nearly the whole page on a few
very long physical lines, combining mappings, authorization lookup, forms,
equipment, people, documents, and history. The PHP acceptance test and browser
test use the same style (`tests/Yii2/yii2_object_card_presentation_001_test.php:3-20`,
`tests/Yii2/object_card_presentation_browser.mjs:1-3`). This makes diffs and
failure ownership difficult to inspect and defeats physical-line architecture
advisories. Reformat the code and extract only stable cohesive helpers/partials.

### G5-5 — document-row markup is duplicated

`app/YiiRuntime/Views/object-card.php:40-41` duplicates the public
`shlz-document-row` visual, content, title, metadata, action, link, and accessible
label structure for assignment artifacts and technical documents. Extract one
bounded row renderer/partial with explicit icon, title, metadata, URL, and link
attributes so component and accessibility changes have one owner.

The associated heuristic smells are duplicated code/data clumps and divergent
change in the view. The compressed single-letter locals also create mysterious
names. No actionable speculative generality, message chain, middle-man, or
refused-bequest finding was identified.

## Preserved behavior and evidence

No further blocker was found in the reviewed scope:

- privacy removals suppress position outside the installer directory and remove
  source/provenance/update-origin presentation from the in-scope views;
- document rows use public `shlz-document-row` structure, public format icon
  classes, complete available names/metadata, separate actions, and unchanged
  URLs;
- authorization checks and mutation controls remain capability-gated; no new
  persistence owner or write route was introduced;
- the responsive CSS provides bounded desktop surfaces, one-column mobile fact
  and person layouts, reachable horizontally scrolling tabs, and two-column
  mobile document rows;
- the reviewed browser flow covers matching tab/panel relationships, keyboard
  transitions, panel state, no-JavaScript first-panel content, desktop/mobile
  overflow, production sidebar, and no-write snapshots.

The three package records are exact-source GREEN for the focused acceptance,
change-verification, and architecture-guard commands. They establish successful
execution at candidate source
`a1b27c531e77c9c0c7791e7926c3fc83bb8d1cbc44e9c78fb53152a64d260322`,
but their assertions do not resolve G5-1 or G5-3, and the browser expectation
actively preserves G5-2. Exact-source CI remains outside this review package and
is not claimed here.

## Final decision

Standards: **CHANGES_REQUESTED**. Spec: **CHANGES_REQUESTED**. Overall Gate 5:
**CHANGES_REQUESTED**. Correct the findings, capture fresh exact-source GREEN
evidence, and request a new independent final review.

## Correction rereview — 2026-09-21

- Aggregating reviewer: independent agent `/root/final_object_card_review`
- Standards reviewer: independent agent `/root/final_object_card_review/standards_axis`
- Spec reviewer: independent agent `/root/final_object_card_review/spec_axis`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T224556Z-fc69f7e6c4/package.json`
- Exact candidate source: `9d8907b2c9c2a034a943a1b49e5bd87607bdc8edb009076dec73b5f30ea7dc2a`
- Verdict: **CHANGES_REQUESTED**

### Prior finding disposition

- **G5-1 partially resolved.** The card now overlays immutable selection members
  with current workforce-catalog status, and the focused test changes installer
  7001 to `dismissed` after selection and binds ФИО, personnel number, and
  «Уволен» in the same team row. A missing or unrecognized catalog status still
  renders «Статус не указан» (`app/YiiRuntime/Views/object-card.php:65-70`), while
  A4 still says every installer SHALL have current status «Работает»/«Уволен».
  The test does not cover the missing/unrecognized case. Either make the current
  projection complete/fail safely or specify and test the truthful unknown state.
- **G5-2 resolved.** `app/YiiRuntime/Assets/navigation.js:7` delegates tabs solely
  to public `enhanceTabs(document)`. The post-processing shim is gone, and
  `tests/Yii2/object_card_presentation_browser.mjs:10` now asserts the public
  controller's `0/-1` roving-tabindex state after every transition.
- **G5-3 behavior resolved by contract alignment, but lifecycle review is
  missing.** A3 now describes the actually available planned/actual dates,
  process readiness, technical characteristics, and explicit absence of a
  delivery projection. `app/YiiRuntime/Views/object-card.php:108-110` renders
  those facts, while `tests/Yii2/yii2_object_card_presentation_001_test.php:11-14`
  uses panel-scoped label/value assertions and rejects an invented actual start.
  However, both the normative spec and acceptance test changed after the recorded
  Gate 3 approval. That approval requires renewed review after either change, and
  this package reports Gate 3 `MISSING`. Exact-source GREEN is not a substitute
  for independent Gate 3 approval.
- **G5-4 remains open.** Helper definitions in
  `app/YiiRuntime/Views/object-card.php:8-94` are now readable, but rendered UI
  lines 96-121 still compress actions, authorization, forms, four panels, and
  history into very long physical lines. The PHP acceptance test lines 3-24 and
  browser test lines 3-13 remain similarly compressed. Normal formatting and
  bounded cohesive decomposition are still required for reviewable ownership.
- **G5-5 resolved.** The `$documentRow` renderer at
  `app/YiiRuntime/Views/object-card.php:72-90` owns the shared public component
  markup, and both document families call it.

### New blocking finding — SQL is owned by the HTTP controller

`app/YiiRuntime/Controllers/ObjectCardController.php:110-140` directly constructs
and executes the current-workforce query against `fm2_workforce_catalog`, builds
parameters, interprets rows, and merges them into the card projection. This
violates `docs/architecture/guardrails.md` rule 2, which confines business SQL to
MariaDB adapters and permits HTTP/controller code to consume named read adapters.
It also puts projection ownership in the delivery mechanism rather than the
explicit application/read seam.

Move this batched enrichment into the named MariaDB object-card projection/read
adapter and let the controller call that seam. Keep the immutable selection
snapshot unchanged. The fresh architecture-guard GREEN demonstrates that the
current token checks did not catch this documented ownership violation; it does
not waive the rule.

### Evidence and preservation

The package contains three fresh exact-source GREEN records for the focused
HTTP/browser/no-write acceptance test, change verification, and architecture
guard at source
`9d8907b2c9c2a034a943a1b49e5bd87607bdc8edb009076dec73b5f30ea7dc2a`.
No new authorization, privacy, document-row, responsive, or mutation regression
was found. The status enrichment runs after `objects.read`, is a batched SELECT,
and does not alter the protected projection; GET/HEAD and browser fact snapshots
remain equal, and read-only mutation controls remain absent.

Standards: **CHANGES_REQUESTED**. Spec: **CHANGES_REQUESTED**. Overall correction
rereview: **CHANGES_REQUESTED**. Resolve the adapter ownership and A4 unknown-state
contract, complete the formatting correction, obtain renewed Gate 3 approval for
the changed spec/tests, then request another independent final review.

## Final correction rereview — 2026-09-21

- Aggregating reviewer: independent agent `/root/final_object_card_review`
- Standards reviewer: independent agent `/root/final_object_card_review/standards_axis`
- Spec reviewer: independent agent `/root/final_object_card_review/spec_axis`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T225327Z-0c044e98a2/package.json`
- Exact candidate source: `36aa0705157b367480ee64b6ce772d8b555ba28e603d757135807ab4fdab4fb5`
- Verdict: **CHANGES_REQUESTED**

### Resolved findings

- **G5-1 resolved.** After `objects.read`,
  `app/YiiRuntime/Controllers/ObjectCardController.php:111-147` enriches the
  immutable order members through the existing public
  `MariaDbYiiInstallerDirectory::read()` seam. It exact-matches each requested
  personnel number, accepts only canonical `employed`/`dismissed`, and fails the
  card safely on missing, ambiguous, or invalid status. The view therefore emits
  only A4's «Работает»/«Уволен» values. The test changes current catalog status
  after selection and binds ФИО, personnel number, and «Уволен» in one row.
- **G5-2 remains resolved.** Yii delegates to public `enhanceTabs`; browser
  assertions use the component's `0/-1` roving-tabindex contract.
- **G5-3 resolved.** The aligned A3 contract, implementation, and panel-scoped
  XPath assertions cover planned dates, process/original readiness, technical
  facts, truthful absent delivery data, and absence of an invented actual start.
  The incremental Gate 3 record culminates in explicit final A3 approval.
- **G5-5 remains resolved.** One document-row renderer owns the public component
  markup for both document families.
- **The controller-SQL finding is resolved.** The controller contains no new SQL,
  query builder, or table name. It consumes the existing named MariaDB directory
  adapter, which is the documented guardrail exception. The protected workforce
  adapter itself has zero delta.

No new spec, authorization, privacy, no-write, document, accessibility, or
responsive finding was identified.

### Remaining finding — G5-4 formatting and reviewability

G5-4 is only partially corrected. The PHP acceptance test is substantially
reformatted and its names are clearer, and the view's helper definitions are
readable. However, `app/YiiRuntime/Views/object-card.php:95-120` still expresses
the production page across a small number of giant lines (many 700–1300
characters), combining action forms, authorization, readiness, equipment, team,
documents, and history. `tests/Yii2/object_card_presentation_browser.mjs:3-13`
likewise retains long statement chains; its interaction-state line remains over
one thousand characters.

This is the same recorded maintainability finding, not a new stylistic
preference. The remaining compression obscures structure, makes review and
failure localization materially harder, and defeats useful physical-line
architecture metadata. Reformat the production markup and browser scenarios
into ordinary structural lines. No further helper extraction is required beyond
the already-correct document helper.

### Evidence and decision

The three package records are exact-source GREEN for the focused
HTTP/browser/no-write acceptance, change-verification, and architecture-guard
commands at source
`36aa0705157b367480ee64b6ce772d8b555ba28e603d757135807ab4fdab4fb5`;
their detector inventories are empty. Exact-source CI remains separate and is
not claimed by this review.

Spec: **APPROVED** with no findings. Standards: **CHANGES_REQUESTED** for the
single remaining G5-4 issue. Overall Gate 5: **CHANGES_REQUESTED**. Reformat the
two remaining compressed files, capture fresh exact-source evidence, and request
one final independent correction rereview.

## G5-4-only final rereview — 2026-09-21

- Aggregating reviewer: independent agent `/root/final_object_card_review`
- Standards reviewer: independent agent `/root/final_object_card_review/standards_axis`
- Spec reviewer: independent agent `/root/final_object_card_review/spec_axis`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T230002Z-8c94465696/package.json`
- Exact candidate source: `b11940226f8daa4a4bff73a89b6f00c12311d1c25c592dddef6c8ca7bf6a5ffd`
- Verdict: **APPROVED**

G5-4 is resolved. `app/YiiRuntime/Views/object-card.php:95-235` now exposes the
page header and actions, passport, tab list, readiness, team, documents, and
history as ordinary structural blocks. A few cohesive leaf rows remain compact,
but unrelated sections and control flow are no longer collapsed together.
`tests/Yii2/object_card_presentation_browser.mjs:11-139` separately presents
login, desktop setup, tab-state validation, relationship checks, each keyboard
transition, responsive assertions, no-JavaScript behavior, and cleanup.

The bounded delta is formatting-only for these two files. It preserves selectors,
conditions, user-visible text, attributes, authorization branches, public Tabs
`0/-1` expectations, interaction order, responsive checks, and cleanup. The
normative spec and PHP acceptance-test hashes are unchanged from the previously
approved candidate. All earlier findings remain closed: current workforce status
uses the existing named directory read seam and fails safely, public tabs own
their state, A3 is aligned and panel-tested, document rows have one markup owner,
and no new controller SQL was introduced.

The package contains three drift-free exact-source GREEN records for the focused
HTTP/browser/no-write acceptance, change verification, and architecture guard at
source `b11940226f8daa4a4bff73a89b6f00c12311d1c25c592dddef6c8ca7bf6a5ffd`.
Their detector inventories are empty. `git diff --check`, PHP syntax, and Node
syntax checks are clean. Exact-source CI remains a separate delivery obligation
and is not claimed by this review.

Standards: **APPROVED**, 0 findings. Spec: **APPROVED**, 0 findings. Overall Gate
5 for the exact candidate above: **APPROVED**.

## Owner-directed visual composition rereview — 2026-09-21

- Aggregating reviewer: independent agent `/root/final_object_card_review`
- Standards/security reviewer: independent agent `/root/final_object_card_review/standards_axis`
- Spec reviewer: independent agent `/root/final_object_card_review/spec_axis`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T231353Z-7565d4eaa1/package.json`
- Exact candidate source: `0eaa206722ddeb2e9710adb8e582a4c984b9082aa59cc4049b3e981373ff5396`
- Verdict: **CHANGES_REQUESTED**

### Visual and specification review

The owner-directed composition is implemented and sensitively tested. The main
and delta contracts agree that A1 owns a narrow left passport containing the
available technical characteristics and a wider right workspace, while A3 owns
dates, process readiness, and the truthful missing-delivery message and excludes
technical facts from the tab.

`app/YiiRuntime/Views/object-card.php:141-190` renders one passport-first,
workspace-second grid and places projected technical facts only in the passport.
`app/YiiRuntime/Assets/pilot.css:1050-1055` gives the passport a 270–320px desktop
track and the workspace the flexible track; the existing ≤900px rule collapses
the layout. Playwright measures actual left/right position, equal top alignment,
and narrower passport at 1440px, then verifies mobile overflow and last-tab
reachability. The DOM test binds floors, capacity, and speed to the passport and
rejects them inside readiness. The incremental Gate 3 record explicitly approves
the aligned mock-fidelity specification correction.

Document rows use the public row structure, canonical SHLZ file-type SVG names,
and the canonical download SVG via decorative `<img alt="">` elements; the action
retains an accessible label. The route and controller maps are finite exact
allowlists. Request input is used only as a map key and is never concatenated into
a filesystem path, so no direct traversal path was found. Prior authorization,
privacy, no-write, tabs, status projection, and maintainability findings remain
closed.

### Finding 1 — immutable caching on non-versioned dependency URLs

`app/YiiRuntime/Controllers/PilotAssetController.php:21-24,42` serves the new SHLZ
SVGs at stable paths such as
`/pilot/assets/shlz-file-types/file-pdf-default.svg` and
`/pilot/assets/shlz-icons/download.svg` with `Cache-Control: public,
max-age=31536000, immutable`. The URLs contain no digest or dependency/release
version, while the bytes can change when the separately built `shlz-ui`
dependency changes. Browsers may therefore retain obsolete assets for a year
across application deployments.

Use fingerprinted/versioned URLs before applying `immutable`, or give these
stable SHLZ dependency URLs the existing short cache/revalidation policy used by
the non-versioned SHLZ behavior asset.

### Finding 2 — the new public asset seam is not exercised

`tests/Yii2/yii2_object_card_presentation_001_test.php:41,92` proves that the
rendered card contains the expected SVG URLs, but neither it nor the browser test
requests those URLs. Missing dependency files/root wiring, route mismatch, wrong
bytes, MIME or cache/security headers, broken HEAD behavior, and accidental route
admission can all pass the supplied focused GREEN. The canonical production web
cutover inventory was not extended for these assets either.

Add bounded real-HTTP checks for representative file-type and action SVGs that
verify the public-export bytes or digest, `image/svg+xml`, the intended cache
policy, `X-Content-Type-Options: nosniff`, `Cross-Origin-Resource-Policy:
same-origin`, GET/HEAD parity, and rejection of unknown and traversal-shaped
paths. Register the new canonical public assets in the existing production web
cutover contract where that inventory owns the deployed seam.

### Evidence and decision

The three package records are exact-source GREEN for the focused card/browser,
change-verification, and architecture-guard commands at source
`0eaa206722ddeb2e9710adb8e582a4c984b9082aa59cc4049b3e981373ff5396`;
their detector inventories are empty. They do not exercise the new asset delivery
boundary described above. Exact-source CI remains separate and is not claimed.

Spec/visual axis: **APPROVED**, 0 findings. Standards/security axis:
**CHANGES_REQUESTED**, 2 findings. Overall Gate 5: **CHANGES_REQUESTED**.

## Asset-security correction rereview — 2026-09-21

- Aggregating reviewer: independent agent `/root/final_object_card_review`
- Standards/security reviewer: independent agent `/root/final_object_card_review/standards_axis`
- Spec reviewer: independent agent `/root/final_object_card_review/spec_axis`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T231919Z-78000a2f4c/package.json`
- Exact candidate source: `6255b51cb90d058f1c57a50fffb3695db4a188bdf3e8ad9e6c909f0ef5ab8146`
- Verdict: **CHANGES_REQUESTED**

The unsafe cache policy is resolved. The new stable, non-versioned SHLZ SVG
paths now use `public, max-age=3600` without `immutable` in
`app/YiiRuntime/Controllers/PilotAssetController.php:21-24,42`.

The focused public-seam coverage is also resolved.
`tests/Yii2/yii2_object_card_presentation_001_test.php:47-65` requests the two
representative SVGs through the real unauthenticated Yii HTTP route, compares
their response bodies byte-for-byte with the canonical public `../shlz-ui`
exports, and verifies exact SVG MIME, one-hour cache policy, `nosniff`, same-origin
CORP, HEAD empty-body/content-length parity, unknown-file rejection, and encoded
traversal-shaped rejection. The incremental Gate 3 record explicitly approves
these expectations.

### Remaining finding — production cutover inventory omits the new assets

The canonical deployed-asset inventory in
`tests/Support/yii2_production_web_cutover_contract.php:75-90` still does not
contain `shlz-file-types/file-pdf-default.svg` or `shlz-icons/download.svg`.
`tests/Runtime/yii2_production_web_cutover_001_test.php:30` derives its exact
packaged-production asset matrix from that inventory. Consequently, the focused
fixture proves the development/test public route, but the production cutover
consumer will not prove that either new dependency asset exists with canonical
bytes and headers in the packaged runtime.

Register both endpoints in the existing production cutover inventory with their
canonical SHA-256 values, `image/svg+xml; charset=UTF-8`, and `public,
max-age=3600` policy. This is the remaining portion of the prior request to
register the new assets where the deployed-seam inventory owns them.

The visual/spec artifacts are byte-identical to the previously approved
candidate, so the two-column composition, passport-only technical facts,
document markup, tabs, responsive behavior, authorization, privacy, and no-write
decisions remain approved. The three package records are drift-free exact-source
GREEN for focused acceptance, change verification, and architecture guard at
source `6255b51cb90d058f1c57a50fffb3695db4a188bdf3e8ad9e6c909f0ef5ab8146`.
Exact-source CI remains separate and is not claimed.

Spec/visual axis: **APPROVED**, 0 findings. Standards/security axis:
**CHANGES_REQUESTED**, 1 remaining finding. Overall Gate 5:
**CHANGES_REQUESTED**.

## Canonical production asset inventory rereview — 2026-09-21

- Aggregating reviewer: independent agent `/root/final_object_card_review`
- Standards/security reviewer: independent agent `/root/final_object_card_review/standards_axis`
- Spec reviewer: independent agent `/root/final_object_card_review/spec_axis`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T232347Z-9787dbac07/package.json`
- Exact candidate source: `ac3c27ec6833a19cf5c02857b2b75f216dcc2244dbff723953d92bf7816cceb1`
- Verdict: **APPROVED**

The sole remaining finding is resolved. The canonical deployed-asset inventory
at `tests/Support/yii2_production_web_cutover_contract.php:81-82` now contains:

- `shlz-file-types/file-pdf-default.svg` with canonical SHA-256
  `97e564168a005b67340870d42bdb828c8469fdb547bd6a22b9d8a97b18352a87`;
- `shlz-icons/download.svg` with canonical SHA-256
  `0b290278a6a0cdfb4986cc98125833f1020cb3cfc0c511e878868d106622bfd5`.

Both hashes match the public `../shlz-ui` distribution files. Both inventory
entries require `image/svg+xml; charset=UTF-8` and `public, max-age=3600`, matching
the corrected non-immutable production asset policy. The inventory also carries
the exact current `pilot.css` and `navigation.js` digests. The existing production
cutover consumer iterates these entries and verifies status, exact body digest,
MIME, cache policy, `nosniff`, same-origin CORP, and inclusion provenance.

All previously approved production, visual, specification, browser, focused-test,
and asset-route bytes are unchanged from the preceding candidate. The package
contains five drift-free exact-source GREEN records at source
`ac3c27ec6833a19cf5c02857b2b75f216dcc2244dbff723953d92bf7816cceb1`:

- focused object-card HTTP/browser/no-write acceptance;
- production web cutover;
- change verification;
- runtime storage integration;
- architecture guard.

Their detector inventories are empty, and `git diff --check` is clean. Exact-source
CI remains a separate delivery obligation and is not claimed by this review.

Standards/security: **APPROVED**, 0 findings. Spec/visual: **APPROVED**, 0
findings. Overall Gate 5 for the exact candidate above: **APPROVED**.

## Document micro-action and inventory rereview — 2026-09-21

- Aggregating reviewer: independent agent `/root/final_object_card_review`
- Standards reviewer: independent agent `/root/final_object_card_review/standards_axis`
- Spec reviewer: independent agent `/root/final_object_card_review/spec_axis`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T233257Z-2e40486b43/package.json`
- Exact candidate source: `c0a919da562d65a3dea6a677d6f574ed4d68b7643cc839e65ef18c1127e8441a`
- Verdict: **APPROVED**

The bounded micro-action delta is correct. In
`app/YiiRuntime/Views/object-card.php:215-220`, «История оригинала» and
«Исправить оригинал» are anchors using the public `shlz-button
shlz-button--secondary` primitive inside the Documents panel. The existing
authorization remains exact: history requires `$canReadOriginal`, correction
requires `$canCorrect`, and the action cluster requires a valid order ID plus at
least one applicable grant. URLs, mutation ownership, and persistence behavior
are unchanged.

`app/YiiRuntime/Assets/pilot.css:1108` adds only scoped cluster layout—wrapping,
gap, and top margin—and does not fork or restyle the public button primitive. The
production cutover inventory records the final `pilot.css` SHA-256
`53f59211dcc5a172d83a42c8e0eeabd506ae3417b7fca7d100a176755311b82e`,
which matches the current file bytes, with the existing CSS MIME and one-hour
cache policy.

The corrected acceptance assertions locate exactly `#object-panel-documents`
and require one anchor per action with the `shlz-button` class token and the
distinct `/originals/history` or `/originals/submit` suffix. They cannot pass via
matching text outside the panel or a non-anchor class holder. The independent
incremental Gate 3 record explicitly approves this XPath correction. The
read-only negative still rejects the correction control, and fact snapshots
preserve no-write behavior.

All five planner-selected obligations are drift-free exact-source GREEN at
`c0a919da562d65a3dea6a677d6f574ed4d68b7643cc839e65ef18c1127e8441a`:
focused object-card/browser acceptance, production web cutover, change
verification, runtime-storage integration, and architecture guard. Their
detector inventories are empty, and `git diff --check` is clean. Exact-source CI
remains separate and is not claimed.

Standards: **APPROVED**, 0 findings. Spec/visual: **APPROVED**, 0 findings.
Overall Gate 5 for the exact candidate above: **APPROVED**.

## Final exact-candidate rereview — 2026-09-21

- Aggregating reviewer: independent agent `/root/final_object_card_review`
- Standards reviewer: independent agent `/root/final_object_card_review/standards_axis`
- Spec reviewer: independent agent `/root/final_object_card_review/spec_axis`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T234450Z-20521bf39d/package.json`
- Exact candidate source: `53fee5f80114afec34e07c44a507ee76a49a09284da6ba8504a00e4a855ea76d`
- Verdict: **APPROVED**

The final owner-directed delta conforms to the approved composition. The
context-sensitive primary action is inside the wider right
`.fm2-object-workspace`, before its tabs, for each applicable state branch.
The left passport is limited to projected technical characteristics; identity
and status remain in the page header, and process readiness remains in its tab.
DOM and browser assertions independently prove the action/workspace relationship
and the desktop geometry.

Workspace actions consistently use public SHLZ buttons. This includes the
deadline-certificate action, the capability-gated «Изменить состав бригады»
selection action, engineer assignment, and the separately gated document history
and correction actions. Local `.fm2-workspace-actions` and
`.fm2-document-actions` rules own only wrapping and spacing. No route,
authorization, mutation, or persistence ownership changed.

The view-local technical presentation mapper is bounded and pure. It maps only
the owner-approved numeric enum values `pittype=40`, `pitmaterial=41`, and
`paired=39` to «Глухая», «Железобетон», and «Первая». Already textual display
values pass through, ordinary numeric measurements remain visible, and unknown
numeric enum codes become «Не указано» rather than leaking internal codes. All
output remains escaped. Fixture values deliberately provide numeric `display`
values; passport-scoped label/value assertions and negative raw-code checks make
the acceptance sensitive. The incremental Gate 3 record approves this mapping.

`PreopeningAssetBundle.php` derives 12-hex query versions from the local
`pilot.css` and `navigation.js` bytes without request input. The final full CSS
digest in the production cutover inventory matches the candidate, as do the
previously approved navigation and canonical SVG entries and policies.

All five planner-selected obligations are drift-free exact-source GREEN at
`53fee5f80114afec34e07c44a507ee76a49a09284da6ba8504a00e4a855ea76d`:
focused object-card/browser acceptance, production web cutover, change
verification, runtime-storage integration, and architecture guard. Their
detector inventories are empty, and syntax and `git diff --check` are clean.
Exact-source CI remains separate and is not claimed.

Standards: **APPROVED**, 0 findings. Spec/visual: **APPROVED**, 0 findings.
Overall Gate 5 for the exact candidate above: **APPROVED**.
