# Gate 3 final rereview: YII2-CONSTRUCTION-CONTROL-SERVER-FILTERING-001

- Reviewer: independent Gate 3 agent `/root/gate3_review`; authored none of the reviewed specification, OpenSpec artifacts, tests, browser helper, or production code.
- Review date: 2026-09-22.
- Corrected exact commit: `a083700c4d0dc957ef29dc15903c38da3834391d` (`test: complete construction control filter coverage`).
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T000105Z-343aeb9226/package.json`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T000105Z-343aeb9226/verification-plan.json`, SHA-256 `9ef452e7be1ebeb6ea80f9d4890d2553d3bb6d9da2f43ad0e15edc4a26134571`.
- Prepared head: `2d4ebe3b7e9aed44a3305000905dcf6baf27c23f`; executable source digest `33b0f7f1e3f86f86cc48d6993da0db581719fded7ebd45074bc549fc41431dd2`. The final correction commit contains the prepared corrected bytes plus review history; the plan binds the corrected PHP test and browser helper.
- Corrected test binding: `8d984d8eca7a0e705db309e2a99f6c2be1c97dddc40982e426a72f0cd46d6a06`.
- Corrected browser-helper binding: `f3d764e265dae9a4f784be7eaf5995502597afb97ecd6d932c9f9ad4fac2a06b`.
- Planner decision: `CRITICAL`; required reviews: `gate3`, `final`; `missing_tests` is empty.
- Fresh disposable browser-profile RED: exit `255`; the default-mine assertion at corrected test line 13 expected one row and observed 50. The run reached real Yii HTTP and the isolated MariaDB fixture at source digest `33b0f7f1e3f86f86cc48d6993da0db581719fded7ebd45074bc549fc41431dd2`.
- Verdict: `APPROVED`.

## Prior-finding resolution

1. **Resolved — native-current ownership is now distinguished from prohibited fallbacks.** The corrected fixtures include a legacy-only actor association with current native ownership by another engineer and a historical actor assignment followed by a current reassignment. Default mine must exclude foreign-current, legacy-only, and historical-only objects, while `ownership=all` must retain the latter objects. The independently assigned current tail remains the positive mine witness. This matrix fails if implementation ORs current ownership with legacy or historical ownership.

2. **Resolved — ordering, out-of-range behavior, and page-reset targets are observable.** The PHP test now derives exact first- and second-page object-ID order from the fixture ranges, retains the total/unique-set checks, and requires an out-of-range page to return the established controlled response without substitute rows. The browser starts at an actual URL containing `page=2`, serializes the real filter form into its action target after search change and clear, and proves the current URL still contains page 2 while each target omits it. These checks no longer pass merely because the form never had a page field.

3. **Resolved — browser preservation is behavioral rather than lexical.** Before loading production JavaScript, the helper seeds a scoped queued operation in IndexedDB and installs an observable service-worker postMessage witness. After initialization it requires the matching row to be painted `queued`, observes at least one prefetch post, and checks shipment state plus a checklist link for every rendered row. It simultaneously proves the server-provided 50 rows and 55-object total are not rewritten by browser filtering. Together with the unchanged focused browser/shipment/checklist consumers selected by the verification plan, this is sensitive to the bounded preservation promises without performing production I/O.

## Complete Gate 3 assessment

No findings remain. The normative specification and OpenSpec delta consistently define a read-only server-filtering slice. The test cites the specification, crosses the authorized GET/HEAD Yii seam, and uses an isolated disposable database. Expected values are independently determined from fixed fixture identities, assignment sequences, completion facts, effective-detail edits, and explicit page ranges rather than implementation internals.

The complete matrix covers default and explicit ownership, current versus historical/legacy ownership, permission preservation, effective address/registration search, trimming and case behavior, literal LIKE metacharacters, malformed scalar shapes and query length rejection, canonical completion and PTO-only exclusion, coherent rows/count/pages, stable ordering, filter-preserving links, controlled out-of-range input, refresh, truthful empty state, valid and invalid HEAD, fact/assignment/detail immutability, page reset, server-owned rows/total, IndexedDB state painting, sync/prefetch entrypoints, shipment indication, and checklist navigation.

The fresh RED is deterministic and sensitive to the missing behavior: fixture and runtime setup complete, then the first default-mine public-seam assertion receives the unfiltered 50-row page instead of the single current-native assignment. The package's `evidence` array remains empty, so the delivery record should preserve the reported RED command/result/source digest (or its retained record path) explicitly; this bookkeeping point does not invalidate the reviewed source-bound RED described above.

CI, implementation GREEN, final review, merge, and deployment remain `UNKNOWN`. This Gate 3 approval treats none of them as complete or authorized.

## Verdict

`APPROVED`

Gate 4 may proceed against the corrected test/package. Any later change to the specification, PHP expectations, browser helper, verification input, or source bindings requires applicable independent delta review.
