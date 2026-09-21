# YII2-CONSTRUCTION-CONTROL-SHIPMENT-INDICATOR-001 — Gate 5 final review

- Reviewer: independent Codex reviewer `/root/issue16_gate3`; authored neither implementation nor root tests
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T144332Z-c3a65a9e4a/package.json`
- Exact reviewed source: base `80130fbb3bb7998a1a0fc6e88429699244715540` plus reconstructible snapshot, harness source `1ac80ac5e5a9e50083faa9dd53179da48f3874a4388d3676e5cccae4e0133596`, executable source `656280c0c160b4c4b24e625af4f0400b3f75147ab3f98f533d4cd47df72ff300`
- Snapshot patch SHA-256: `95465f73b6edfed8fcc8a32806c7adae7d7e96ba900d17a4654caf364d0cc9b3`
- Verification plan SHA-256: `bfd404b837aacecc464c241d865ea51523c08529bb851abfbc664d77c149af0b`
- Review date: 2026-09-21

## Code and contract assessment

No implementation defect or scope creep was found.

- `MariaDbYiiChecklistRead` extends the existing queue read with one `LEFT JOIN` to the authoritative current equipment-fact projection and exposes only the two nullable shipment dates. It introduces no writer, fallback fact, freshness inference, or lifecycle mutation. Projection absence therefore remains unknown, and physical projection unavailability follows the existing runtime-error/503 contour.
- The view applies the required full-before-first priority, emits no indicator without either confirmed date, and renders exactly one native `<details>/<summary>` disclosure. The summary has the state-specific accessible name; the expanded content binds the same visible title to the selected date formatted `DD.MM.YYYY`; the icon itself remains decorative.
- The two states use distinct public SHLZ forms. The checked-in `delivery-box.svg` and `delivery-4.svg` hashes are byte-identical to `../shlz-ui/packages/icons/dist/icons/` (`b4517454…` and `e79f74ce…`). No new local icon design or external dependency was introduced.
- CSS keeps the desktop indicator bounded to 220px, gives the native summary a 44px target, and moves the opened detail into the mobile flow at the declared breakpoint. The queue client adds one early guard for `data-shipment-disclosure`; direct checklist links and the existing row-navigation owner remain otherwise unchanged.
- The diff is limited to the read projection, queue view/assets, exact public icons, registered acceptance/governance entries, and the approved OpenSpec/spec/test/review artifacts. Schema, sync jobs, writes, filters, object card, notifications, and unrelated domain behavior are unchanged.

The exact-source acceptance record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790001770404434000-40ff82c2638043bea095ff3a738f5e55.json` is drift-free GREEN. It exercises positive-state priority, full-without-first, negative/unknown/error states, exact asset hashes, native accessibility/content/layout/navigation source contract, GET/repeat/HEAD/denied read-only inventories, and unavailable-projection 503 behavior. `openspec validate show-equipment-shipment-in-control-queue --strict` and `git diff --check` are also clean in this review.

## Complete finding

1. **HIGH — the final package supplies GREEN evidence for only 1 of 13 planner-selected local obligations.** The package's `local_obligations` includes the adjacent inspection browser, schema/photo characterization tests, active queue, preopening, inspection journey, compose, change-verification, and architecture-guard checks in addition to the new acceptance. Its `evidence` contains only `acceptance:yii2_construction_control_shipment_indicator_001_test`; no exact-source results are supplied for the other twelve obligations. Under the repository rule that missing checks remain `UNKNOWN`, those controls cannot be treated as GREEN merely because the implementation is small or the primary acceptance passes. Run the complete planner-selected bounded local set against this unchanged exact source, retain the results in the final prepared package/record, and resubmit for a narrow evidence rereview. Do not run the prohibited full `make test` or `make verify` suite.

CI and deployment remain `UNKNOWN`; neither is treated as approval or GREEN.

## Verdict

`CHANGES_REQUESTED`

The implementation and primary acceptance are approved in substance. Gate 5 is blocked only on complete exact-source evidence for the planner-selected focused obligations; no production-code correction is requested by this review.

---

## Gate 5 evidence and tooling rereview — 2026-09-21

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T150427Z-be4f079156/package.json`
- Exact source: base `80130fbb3bb7998a1a0fc6e88429699244715540` plus reconstructible snapshot, harness source `893e23227b98416417862483a866a9bd0b19d2581f564416244ad41ed96aaf3f`, executable source `715fcce0023a5d36e513bdc325643dcc909c7de72ba6eeeb95269929a0d8d861`
- Snapshot patch SHA-256: `9a54932ca06b2f27b89bb1af97dcc0aa4900a2272c3da9871e9d71d5a62e669f`
- Verification plan SHA-256: `180238b6e640352edb274499f715997a2c4eac952af6accba90a1cbf16dc2fd8`
- Owner authorization: explicit request to install and qualify Playwright in the focused browser profile; no production image/runtime change authorized or present

### Prior finding disposition

The sole prior finding is resolved. The complete planner-selected bounded set is reported GREEN at the current exact executable source:

- shipment-indicator acceptance under the integration profile, source-bound record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790003029827175000-06a3ccc7b23848b1a29f655aef041e79.json`;
- `yii2_inspection_browser_001_test.php`, active queue, preopening, and inspection journey under the browser profile;
- inspection-evidence schema and all four selected photo characterization controls under the integration profile;
- pilot jobs Compose, change verification (`18/18`), and architecture guard (`59/59`);
- strict OpenSpec validation, `git diff --check`, and the selected impeccable detector.

No canonical local full `make test` or `make verify` suite was run. The focused results close all 13 local obligations from the prepared plan; no failure is reported unresolved.

### Tooling review

The owner-authorized browser-profile correction is bounded and technically coherent.

- The pinned `shlz-ui` stage now builds its public behaviors package before the focused image is finalized. This supplies an existing test dependency; it does not copy behavior code into the application runtime.
- Chromium browser bytes remain pinned under `/ms-playwright`, and `install-deps chromium` is applied only to the `browser` target. Governance/integration and production images are not expanded with browser system libraries.
- `FMONITOR_TEST_PLAYWRIGHT_MODULE` points browser consumers at the image-owned pinned module. The runner continues to execute the frozen reconstructible candidate and verify image/source labels before starting the command.
- `.test-artifacts` is a test-owned writable tmpfs, alongside the existing `/tmp`/`.local` handling, while `/workspace` remains read-only. Browser evidence can therefore be materialized without granting writes to candidate source.
- The change is recorded in the OpenSpec design as an explicit owner-directed focused-verification prerequisite. No product behavior, deployment contour, secret handling, or live stand state is coupled to it.

The product implementation reviewed previously is unchanged in semantics and remains within scope. Exact SHLZ icon hashes, read-only projection behavior, accessible native disclosure, responsive presentation, and row-navigation isolation remain covered and GREEN.

CI and deployment remain `UNKNOWN`; this review does not treat either as GREEN and does not authorize merge or deployment.

### Final verdict

`APPROVED`

Gate 5 passes for exact source `893e23227b98416417862483a866a9bd0b19d2581f564416244ad41ed96aaf3f` / executable source `715fcce0023a5d36e513bdc325643dcc909c7de72ba6eeeb95269929a0d8d861`. No code, test, tooling, or evidence finding remains in the reviewed scope. Any later source or expectation change requires fresh bound verification and independent review.

---

## Bounded focused-tooling delta review — 2026-09-21

- Reviewed commit: `001db2578fe5a4a536f46b1a83fb0f086561610a` (`feat: show shipment status in control queue`)
- Requested scope: `tools/delivery/Dockerfile.focused-checks` and `tools/delivery/run-in-profile`
- Repository state at review: clean; `HEAD` equals `001db257`, so there is no uncommitted or post-commit source delta beyond the tooling already contained in that exact commit

### Assessment

No finding.

The two focused-test changes are bounded responses to observed setup failures:

- `npm --prefix /shlz-ui/packages/behaviors run build` materializes the pinned public browser behavior bundle required by existing Playwright consumers and closes the observed asset 404. It executes in the dependency stage at the pinned `SHLZ_UI_REVISION`; it does not alter application or production assets.
- `--tmpfs /workspace/.test-artifacts:rw,nosuid,nodev` supplies the test-owned write target required by schema/browser evidence while preserving the read-only candidate workspace. It is ephemeral, non-executable by mount policy, and removed with the focused container.

The already-reviewed browser target retains pinned Chromium bytes and browser-only system dependencies. The runner retains frozen-source restoration plus executable-source/image-label checks before execution. Neither change expands production images, runtime permissions, deployment behavior, secrets, or live stand authority.

The bounded exact-source results are GREEN across the Playwright module probe, Yii inspection browser, inspection schema and four photo characterizations, active queue, preopening, inspection journey, and the selected Python governance checks. No full local suite was run.

The first exact-commit CI attempt failed before tests on an external HTTP 504 while downloading a pinned Python artifact. That run is not GREEN, but the failure is upstream dependency transport rather than a product/tooling assertion and does not contradict the bounded local qualification. CI remains unresolved until a complete exact-source run succeeds; this review neither retries CI nor treats the failed run as approval.

### Delta verdict

`APPROVED`

The focused tooling delta is approved as committed in `001db257`. No correction is required. This approval does not alter the existing rule that publication/merge admission still requires its independently tracked exact-source CI disposition.

---

## Post-main-merge Gate 5 delta review — 2026-09-21

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T152857Z-d0e0e55e0a/package.json`
- Updated base: `557166d918b16933538510c489ce5c99c2abaee0` (`origin/main`)
- Merge candidate: `d49b0436d66516ac950db045c4b246e38bac8416`
- Exact harness source: `89371fa47f1b80079e371c1fae96e3fb4d6f385727798b3d4ed1a936448587c7`
- Exact executable source: `27a59c6a0d7cee33acba02922f2808bbbfb03a6aeb724ad8a2540a016d4825dd`
- Verification plan SHA-256: `1f33704c17fda170129ec039782eeff3f182d3611fb6accb337ff3f578cc5845`
- Snapshot patch SHA-256: `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855` (clean committed candidate; empty reconstruction patch)

### Merge assessment

No finding.

The merge has the expected two parents and no conflict-resolution hunk. Direct comparison with the updated main base preserves the complete shipment slice: read-only equipment-fact join, first/full projection fields, full-before-first view semantics, exact SHLZ assets, native disclosure, responsive CSS, navigation guard, focused-profile support, tests, specifications, and review history.

The only overlapping product stylesheet, `pilot.css`, is correctly composed. Against new main, the shipment delta remains the same bounded eight-rule block at the construction-control styles. Against the pre-merge feature parent, the merge retains the intervening mainline dashboard/navigation/asset-version stylesheet work. No shipment selector was dropped or overwritten, and no new-main selector was reverted.

No unexpected product paths appear in the feature delta against `557166d9`; the additional mainline dashboard files visible in the merge history belong to the second parent rather than issue-16 scope.

### Post-merge evidence

The source-bound acceptance record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790004509015756000-66d62e5676554a498580df5462989219.json` is drift-free GREEN on merge commit `d49b0436` and executable source `27a59c6a…`. The full browser journey is also reported GREEN on that executable source. Strict OpenSpec validation and diff-check are clean.

These results cover the only plausible merge-loss boundaries: server projection/rendering, exact assets, CSS/source interaction contract, unavailable projection, and live browser composition after the automatic stylesheet merge.

CI and deployment retain their separately tracked dispositions; this review does not authorize deployment or infer any unreported external GREEN.

### Post-merge verdict

`APPROVED`

Gate 5 remains approved for merged exact source `89371fa47f1b80079e371c1fae96e3fb4d6f385727798b3d4ed1a936448587c7` / executable source `27a59c6a0d7cee33acba02922f2808bbbfb03a6aeb724ad8a2540a016d4825dd`. No behavior was lost and no conflict or scope finding remains.
