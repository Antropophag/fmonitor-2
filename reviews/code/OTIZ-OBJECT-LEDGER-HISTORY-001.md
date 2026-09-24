# Code review: OTIZ-OBJECT-LEDGER-HISTORY-001

- Reviewer: independent Gate 5 agent `/root/final_review` (`gpt-5.6-sol / low`); authored none of the specification, OpenSpec artifacts, tests, fixtures, or implementation and is distinct from the Gate 3 reviewer and executor
- Implementation author: separate Gate 4 executor
- Reviewed source: commit `fac7ac8afa9a8eb2de0b4184cc22e457332bbac3`, candidate source `e3abded39a013cfa693e57a4ef7f36faf10aaa5d17acd4a18586fca5666eeab0`, executable source `0a835283abc40c2e5c3bbafbf6b0b0041a008800713c46946197433908587021`, base `b1542f92009b8dc4216a36962ff38a51e0b6c388`
- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T023500Z-bdabf79b12/package.json`; verification plan SHA-256 `8993789f275d399b75bc00298f10f13f2eef0d6bd16c3318c809845793d91b2f`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T023500Z-bdabf79b12/snapshot/source.patch`, SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855` (empty patch over the reviewed committed source)
- Specification: `specs/OTIZ-OBJECT-LEDGER-HISTORY-001.md`; OpenSpec change `object-ledger-history`
- Approved Gate 3 review: `reviews/tests/OTIZ-OBJECT-LEDGER-HISTORY-001.md`, verdict `APPROVED` for the recorded root-authored test/spec source
- Agreed scope: complete base-to-candidate production/spec/test/lifecycle diff, A01-A10, retained ordinary snapshot behavior, focused evidence and test sensitivity
- Verdict: `CHANGES_REQUESTED`

## Findings

### 1. Medium / blocking — reversal links can target a row absent from the current page

`app/Otiz/MariaDbOtizSettlementView.php:28` exposes `local_reversal_id` whenever the referenced closure exists anywhere in the selected object's ledger. `app/YiiRuntime/Views/_otiz-object-ledger-history.php:13` then always renders `href="#closure-{id}"`, although only the ten rows of the current page have matching DOM identifiers.

When a reversal and its original fall on different pages, the UI therefore presents a local relation that leads nowhere. This is partial conformance to section 2 and the OpenSpec reversal-link scenario: the relation must be usable without leaking another object. The acceptance test keeps A and C on one page and does not exercise a page boundary between the reversal and its original, so the current GREEN result cannot detect this defect.

Render a link that resolves to the original row's actual page and anchor, or render the local anchor only when the original is present on the current page. Add a regression with the reversal and original split across pages, including the same-object and foreign-object isolation assertions.

### 2. Medium / blocking — a valid maximum integer page can overflow into an invalid SQL offset

`app/YiiRuntime/Controllers/OtizSettlementController.php:28` accepts any positive decimal representable as a PHP integer, including `PHP_INT_MAX`. `app/Otiz/MariaDbOtizSettlementView.php:27-28` calculates `($page-1)*10`; at that boundary PHP promotes the result to a float and interpolates scientific notation into `OFFSET`. MariaDB can reject that SQL and produce a 500 instead of the contract's empty beyond-last page with unchanged full-set totals.

A04 explicitly defines the result for a page after the last non-empty page. The test covers page 3 for a 12-row fixture and invalid `0`, alphabetic, and array values, but not a syntactically valid extreme page. Compute offsets with an overflow-safe bound and return an empty page once the requested offset is beyond the available set (or otherwise safely representable), then add maximum/boundary page coverage.

### 3. Low / non-blocking — closure presentation classification now has two owners

`app/Otiz/MariaDbOtizSettlementView.php:28` introduces a SQL `CASE` for reversal/payment/deduction while `app/YiiRuntime/Views/otiz-snapshot.php:20` retains an independent PHP classification of the same closure facts. This is a possible duplicated-code/divergent-change smell rather than a documented-standard violation, and it does not currently change behavior, but the two definitions can drift if closure kinds evolve. Prefer one projection/helper owner when making the blocking correction, if that can be done without broadening the slice.

## Conforming areas

The SQL projection filters rows, aggregates, and pagination by the selected object; orders by `closed_on DESC, id DESC`; preserves all signed components and the existing global-total formula; and performs object validation, aggregate, and page reads inside one repeatable-read transaction. The foreign-object condition on the reversal join prevents disclosure. Query validation, guest return URL, `otiz.manage` authorization, basis escaping, source-snapshot links, saved-versus-current language, absence of financial actions, and ordinary snapshot lazy behavior otherwise match the contract.

The base-to-candidate file inventory contains no route, navigation, CSS, schema, migration, writer, `rapid-pilot`, XLSX, calculation, norm, or rounding changes. Task 4.2 and the later PR/CI/delivery tasks remain honestly open; CI, PR, merge, deployment, and the remainder of issue #29 are `UNKNOWN`.

## Verification evidence

The mandatory package records `php tests/Yii2/yii2_otiz_object_ledger_history_001_test.php` GREEN on exact candidate source `e3abded3…`, with identical start/end source and exit 0. I additionally ran the remaining planner-selected bounded local obligations against the same clean commit:

- `php tests/Otiz/snapshot_publication_001_test.php` — PASS
- `php tests/Yii2/yii2_otiz_settlement_001_test.php` — PASS
- `python3 tests/Verification/change_verification_001_test.py` — 18 tests PASS
- `python3 tests/Verification/architecture_guard_001_test.py` — 59 tests PASS
- `git diff --check b1542f92009b8dc4216a36962ff38a51e0b6c388...HEAD` — PASS

These checks support the conforming paths but do not cover either boundary finding above. No full local `make test` or `make verify` was run.

## Required changes

1. Make reversal navigation usable when original and reversal rows are separated by pagination, without weakening same-object isolation, and add a sensitive regression.
2. Handle extreme valid page numbers without arithmetic overflow or SQL failure, returning the specified empty beyond-last page with full-set totals, and add boundary coverage.
3. Obtain independent review of the changed test expectations as required, rebuild the exact-source package, rerun all planner-selected focused checks, and return the corrected candidate to Gate 5.

---

## Correction rereview — latest controlling verdict

- Reviewer: independent Gate 5 agent `/root/final_review` (`gpt-5.6-sol / low`); authored none of the correction specification, tests, fixtures, or implementation
- Reviewed source: commit `5ceaf5fc8d2c4cc66548bd29085819366443309e`, candidate source `4c229e2bc9007bb441b794f3b7e099893539f4a9b59dfeb8db39408f5faea27f`, executable source `604bbc5c2af8ddf75050955c24ac69365e4275ea6ff245278191d04dd606e8e8`, original base `b1542f92009b8dc4216a36962ff38a51e0b6c388`
- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T024540Z-04af4a8faa/package.json`, SHA-256 `16ae8f5a68d846f632ef2ebe52ccb9e3b3162f25f391d2b13a4a92647833d927`; plan SHA-256 `3208e62b0f93c2f8ad1fa220822b0f0be3437765114f28979fec6c4c3541e815`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T024540Z-04af4a8faa/snapshot/source.patch`, SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855` (empty patch over the reviewed commit)
- Corrected-test approval: `reviews/tests/OTIZ-OBJECT-LEDGER-HISTORY-001.md`, Gate 5 blocking-correction disposition `APPROVED`
- Verdict: `APPROVED`

### Prior blocking findings disposition

1. **Cross-page reversal navigation: resolved.** The projection now derives `local_reversal_page` from the same object's canonical `closed_on DESC, id DESC` ordering inside the existing repeatable-read transaction. The view keeps the short same-page anchor and emits a current-context page-plus-anchor URL when the original is elsewhere. The join still requires `original.object_id=c.object_id`, so a malformed foreign-object reversal exposes neither an identifier nor a link. The corrected fixture deterministically separates reversal and original across pages and proves both the exact page-2 URL and the destination anchor.
2. **Extreme page overflow: resolved.** Offset multiplication is guarded by `intdiv(PHP_INT_MAX, 10)` before it occurs. A larger valid page returns an empty `rows` set while preserving the aggregate and selected object from the same consistent read; it does not clamp to another page or interpolate a float into SQL. The corrected acceptance test sends `PHP_INT_MAX` through the public HTTP seam and requires status 200, zero rows, and unchanged full-set count and total.
3. **Correction-review and exact-source evidence: resolved.** The root-owned test/spec delta received independent Gate 3 approval, the package is bound to the committed correction source, and every planner-selected local obligation is exact-source GREEN.

### Current findings

One non-blocking maintainability advisory remains: closure presentation classification still exists in both the new SQL projection and the retained current-snapshot PHP view. This is a possible duplicated-code/divergent-change smell, not a documented-standard or specification violation, and does not block this bounded slice. No current blocking findings.

### Reconfirmed conforming areas

The complete original diff plus correction continues to filter, aggregate, order, and paginate only the selected object in SQL; preserve all signed components and the established `global_closed_cents` formula; and bind object validation, totals, page rows, and reversal-page calculation to one repeatable-read transaction. Stable ordering remains `closed_on DESC, id DESC`. Foreign reversal targets remain isolated.

Authorization and query behavior remain unchanged: guests retain the exact safe 303 return URL, authenticated actors require `otiz.manage`, malformed or mismatched context fails without ledger disclosure, and valid out-of-range pages are empty rather than substituted. Basis text is HTML-escaped; source and reversal URLs contain only validated integer context. Saved snapshot values and current all-snapshot totals remain explicitly distinct.

The history remains GET-only and contains no payment, deduction, or reversal commands. Ordinary snapshot rendering, current-snapshot ledger rows, forms, actions, and lazy loading are retained. The correction adds no route, navigation, CSS, schema, migration, writer, XLSX, calculation, norm, rounding, external-call, or `rapid-pilot` change. Task/PR/CI/delivery steps remain truthfully outstanding rather than being inferred from this review.

### Exact-source evidence

All five sequential records are GREEN on candidate source `4c229e2b…` / executable source `604bbc5c…`, with identical start/end source and exit 0:

- acceptance: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790217880425829000-4e8c78fbbc19457f958471d0b07e9d81.json`
- snapshot publication: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790217885042627000-f2ee16cbcf714e0cb095d648f976d58a.json`
- retained settlement: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790217886761501000-8929afb6285845ac8150fcef38de293d.json`
- verification governance: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790217889147231000-fd5f9dabe7444defb6c1808a601cf64a.json`
- architecture guard: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790217912240041000-12242e03827e4bf4adee5fb10083228c.json`

`git diff --check b1542f92009b8dc4216a36962ff38a51e0b6c388...HEAD` is clean. No full local `make test` or `make verify` was run. Exact-source CI, PR, merge, deployment, and completion of the broader issue #29 remain `UNKNOWN` and are not implied by this verdict.

### Required changes

None. Gate 5 is approved for exact committed candidate `5ceaf5fc8d2c4cc66548bd29085819366443309e`. Any subsequent production, specification, test, fixture, browser-helper, verification-input, or source-binding change requires applicable independent delta review.
