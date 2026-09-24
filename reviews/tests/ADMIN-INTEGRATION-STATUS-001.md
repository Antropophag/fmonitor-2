# Independent Gate 3 test review — ADMIN-INTEGRATION-STATUS-001

- Reviewer: separately tasked agent `issue30_gate3`; authored none of the reviewed specification, OpenSpec artifacts, verification input, or tests.
- Review date: 2026-09-24.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015014Z-77a4178cbe/package.json`.
- Exact reviewed source: reconstructible dirty snapshot over base `b1542f92009b8dc4216a36962ff38a51e0b6c388`, source digest `b2522737696dd1dcea4e468212a0888f211602f94b1c62bfb82e80be7c6f7071`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015014Z-77a4178cbe/snapshot/source.patch`, SHA-256 `116ccc00bdd8bd87441bf452a8a446f02bfaed870b7132c0556aa27cadaedfdf` (matches `snapshot/manifest.json`).
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015014Z-77a4178cbe/verification-plan.json`, package-declared SHA-256 `55f6e0941b13371ce6a0e1394b83bdafb982bf7cbe2346f446400a9c8e5e9ffe`.
- Normative inputs: `specs/ADMIN-INTEGRATION-STATUS-001.md`, issue #30 as updated 2026-09-24, and `openspec/changes/add-integration-status-admin/specs/admin/integration-status/spec.md`.

## Findings

1. **HIGH — A1 and A4 are not exercised through the declared authenticated HTTP seam.** Locations: `specs/ADMIN-INTEGRATION-STATUS-001.md:15,18`; `tests/Yii2/yii2_integration_status_001_test.php:11-13,20`. The PHP test only searches planned source text for route/authentication tokens and a short denylist of direct calls. It never requests the route as an allowed admin, active non-admin, guest, or blocked user; never asserts the actual authentication redirect/return path or denial status/body; and never sends GET, HEAD, or rejected methods. It therefore passes implementations that mention `canonicalAccess` without enforcing it, expose protected content on denial, mishandle HEAD/method routing, mutate through a database/dependency abstraction, or invoke a transport whose spelling is absent from the denylist. Correction: use disposable application/database fixtures and the real Yii HTTP entry point for every actor and method; assert statuses, redirects/return path, protected-body absence, and complete before/after durable inventories plus transport/job/retry/outbox sentinels.

2. **HIGH — A2's complete source-state matrix has no behavioral or durable-data oracle.** Locations: `specs/ADMIN-INTEGRATION-STATUS-001.md:16`; `tests/Yii2/yii2_integration_status_001_test.php:14,16-17`. Table names, presentation variable names, and Russian labels do not prove selection of the latest attempt separately from the latest completed success, allowlisted diagnostic extraction, or honest counts. There are no fixtures or rendered-response assertions for never-run, successful zero counts, failed latest attempt with older success, unavailable/partially absent schema, or distinct workforce and ERP outcomes. Unsafe use of `receipt_json`, a wrong timestamp, fabricated counts, or treating read failure as empty success could all pass. Correction: seed independently calculated rows for each state in isolated durable fixtures, request the public page, and assert the exact safe observable result while verifying the stored facts are unchanged.

3. **HIGH — A3 pagination and diagnostic selection are represented only by lexical SQL fragments.** Locations: `specs/ADMIN-INTEGRATION-STATUS-001.md:17`; `tests/Yii2/yii2_integration_status_001_test.php:14-16`; `tests/Yii2/integration_status_browser.mjs:3`. `LIMIT`, `OFFSET`, `COUNT(*)`, reason strings, and page-variable names do not establish a fixed maximum, SQL-level bounded reads, correct totals/bounds, deterministic ordering, selection of only `missing_from_delivery`, `OBJECT_NOT_FOUND`/`OBJECT_AMBIGUOUS`, and dead jobs, or preservation of the other two page parameters. Invalid, negative, nonnumeric, huge, and out-of-range parameters are never sent. The browser helper merely requires some pagination nav and does not navigate it. Correction: create over-page-size mixed qualifying/nonqualifying fixtures, instrument or otherwise observe bounded query behavior, exercise all three parameters independently and in combination, and assert normalization, stable ordering, totals, page bounds, and query-string preservation.

4. **HIGH — the browser/safety artifact is not executed by the registered acceptance test and does not cover the required leakage and escaping cases.** Locations: `tests/Yii2/yii2_integration_status_001_test.php:19-22`; `tests/Yii2/integration_status_browser.mjs:1-3`. The PHP test checks only that the `.mjs` file exists, so its admin desktop/narrow assertions can fail or never run while the registered command reports PASS. Even if invoked separately, the script has no non-admin/guest/blocked cases, no persisted malicious-value fixture, no reader-error canaries, no forbidden-field/body scan, no failed-after-success/empty/unavailable states, and no pagination navigation. Checking `Html::encode()` itself proves the framework helper, not that every persisted value in this view uses it; searching the view for forbidden field-name strings does not detect leaked values, exception messages, URLs, DSNs, tokens, raw JSON, or stack traces supplied by the controller. Correction: make browser execution part of the focused command or register it as an explicit acceptance command; feed distinct canaries through every displayable and error path and assert rendered text/non-execution and absence from all allowed/denied responses at both viewports.

5. **MEDIUM — A5/A7 and repeatable read-only behavior are asserted too weakly for the declared complete A1–A7 mapping.** Locations: `specs/ADMIN-INTEGRATION-STATUS-001.md:19,21,23-25`; `tests/Yii2/yii2_integration_status_001_test.php:17-18`; `tests/Yii2/integration_status_browser.mjs:3`. Class-name presence does not prove usable shlz-ui empty/error/table/card states, and one Tab press only proves that something outside `body` receives focus, not that links and pagination are keyboard reachable with visible focus. The tests also do not observe repeated GET/HEAD, timestamps unchanged by viewing, no write locks/partial state, or the concrete first-slice gap copy required to avoid implying all of #30 is complete. Correction: assert the required empty/error/gap content and relevant semantic UI structure, tab through actual controls at desktop/narrow sizes, and repeat the read requests against before/after fixtures (including a controlled committed writer outcome where practical).

## Traceability, determinism, and RED evidence

The verification input maps one PHP file to the combined A1–A7 acceptance. That mapping materially overstates coverage: the file is predominantly a structural source guard, while the declared seam is authenticated Yii HTTP and rendered browser DOM over disposable durable fixtures. Structural route/package checks can remain supplemental, but they cannot replace the missing behavioral matrix. Expected outcomes are currently labels and implementation spellings rather than independently derived fixture results.

I reran the package-selected command:

`php tests/Yii2/yii2_integration_status_001_test.php`

It fails immediately and deterministically at line 8 with `INTENDED_RED: integration status controller absent` (expected `true`, actual `false`). This is an intended missing-production-file RED rather than broken setup, but no later assertion runs and the browser helper is not launched. The RED therefore does not validate sensitivity to the missing acceptance behaviors described above.

The package-selected lane is `CRITICAL`, with `gate3` and `final` required. Full CI, implementation, PR, and deployment remain `UNKNOWN`; none is treated as GREEN or authorization. No production, specification, or test artifact was changed by this review.

## Verdict

`CHANGES_REQUESTED`

Gate 4 is blocked. Return to Gate 2, replace the combined lexical claim with a complete behavioral HTTP/durable-fixture/browser matrix for the bounded first slice, retain useful structural guards as supplements, capture fresh intended RED at a new exact source, and resubmit the complete package for independent Gate 3 review.

---

## Gate 3 correction rereview — 2026-09-24

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015400Z-6b715b0b89/package.json`.
- Corrected exact source: reconstructible dirty snapshot over base `b1542f92009b8dc4216a36962ff38a51e0b6c388`, source digest `d9394982a979584c00778bf5e1e57a52e35d44a8807c17607b800b8f02182923`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015400Z-6b715b0b89/snapshot/source.patch`, SHA-256 `4c495c588298ff876524ac3075b294c2d28bf5dd5f19c5e15f70e5f7bdf47d1a` (matches `snapshot/manifest.json`).
- Corrected verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015400Z-6b715b0b89/verification-plan.json`, package-declared SHA-256 `8fdfa2e74f99f88f6058ea82e7def64f2fce2fafad0b8778fbf183bd3ea077e7`.
- Reviewer independence is unchanged; this reviewer authored none of the corrected specification, test, browser helper, fixture, OpenSpec artifacts, or verification input.

### Prior-finding disposition

- Prior finding 1 is partially resolved: the correction now starts the real Yii application, logs in an admin and ordinary user, requests guest/admin/non-admin GET, admin HEAD and rejected methods, and compares durable rows before/after. Blocked-user behavior and external-call observation remain absent.
- Prior finding 2 is partially resolved: a failed-after-success plus successful-zero fixture is now rendered for both required sources. Never-run and unavailable outcomes remain unexercised, and the corrected ERP setup has a blocking SQL defect described below.
- Prior finding 3 remains materially open: only ERP diagnostics exceed one page; workforce diagnostics and dead jobs are not populated. The test checks only HTTP 200 for page parameters rather than list membership, totals, bounds, normalization, stable ordering, or preservation of independent parameters.
- Prior finding 4 is partially resolved: the registered PHP command now directly runs the browser helper and compares the raw ERP receipt string against the admin body. Persisted hostile values, error/secret canaries, denied-body coverage, and meaningful browser pagination/safety behavior remain absent.
- Prior finding 5 is partially resolved: repeated GET equality and complete table snapshots now observe stable read-only behavior. UI semantics, actual keyboard traversal, gap copy, and the omitted state/list matrices remain open.

### Corrected-candidate findings

1. **HIGH — the corrected fixture cannot reach the HTTP assertions after production appears.** Location: `tests/Yii2/yii2_integration_status_001_test.php:12`. The second `fm2_equipment_fact_runs` tuple has an unterminated `observed_at` SQL literal: `'2026-09-22 12:00:00.000000,'{}'...`. PHP lint is green because the defect is inside a PHP string, but MariaDB will reject the generated INSERT before the server starts. The current missing-controller guard masks this setup failure. Correction: construct a valid failed-run row (preferably with prepared/bound fixture values), then capture RED that progresses through setup and fails solely at the missing production seam rather than leaving all corrected assertions unreachable.

2. **HIGH — the required A1 blocked-actor case remains absent.** Locations: stable contract A1; delta requirement `Недопущенные actors`; `tests/Yii2/yii2_integration_status_001_test.php:16-19`; `tests/Yii2/UserAccessFixture.php:29-38`. The fixture creates only active users, and the test exercises guest, active ordinary, and active admin. A controller that authorizes a user whose status/activation has been blocked would pass. Denied-body safety is also checked only for the ordinary response and one failure code, not for the guest/blocked paths or the broader protected data. Correction: create or transition a dedicated blocked identity, authenticate/session it through the supported fixture seam, and assert denial plus absence of all protected fixture canaries for guest, ordinary, and blocked outcomes.

3. **HIGH — A2 still lacks never-run and unavailable behavioral cases and does not establish safe error output.** Locations: stable contract A2/A6; delta scenarios `Источник не запускался или недоступен` and `Ошибка reader`; `tests/Yii2/yii2_integration_status_001_test.php:9-18`. Both sources receive runs, so there is no never-run case. No absent/partially unavailable projection or injected reader exception is exercised, so implementations that collapse read failure into empty success, leak an exception/DSN/token/URL/stack, or fail the whole page still pass. The only leakage assertion compares the exact benign receipt JSON string, and no hostile persisted display value is seeded to prove view escaping. Correction: use isolated scenario fixtures for never-run, successful empty, failed-after-success, and controlled unavailable/read-error states; inject distinct markup, raw JSON, exception, DSN/URL/token/stack canaries and assert the specified safe output and absence/non-execution at the HTTP/browser seams.

4. **HIGH — the independent bounded-page contract is not tested for two of three lists and is not behaviorally asserted for the third.** Locations: stable contract A3/A5; delta `Независимая пагинация` and `Несуществующая страница`; `tests/Yii2/yii2_integration_status_001_test.php:14,20`; `tests/Yii2/integration_status_browser.mjs:3`. The correction inserts 27 ERP diagnostics but no `missing_from_delivery` workforce rows and no dead jobs. Requests with combined or invalid parameters assert only status 200; they never verify which rows appear, total/page bounds, normalized page, fixed page size, deterministic ordering, or that changing one page preserves the other query parameters. The browser merely checks that at least one pagination nav exists. Correction: seed over-limit mixed qualifying/nonqualifying fixtures for workforce, ERP, and jobs, assert exact independently derived membership/count/order/bounds for each page and invalid/out-of-range normalization, and follow actual pagination links to verify preservation of the other parameters.

5. **MEDIUM — no-side-effect and browser accessibility witnesses remain incomplete.** Locations: stable contract A4/A5; `tests/Yii2/yii2_integration_status_001_test.php:15,19-22`; `tests/Yii2/integration_status_browser.mjs:3`. Row snapshots correctly catch writes to the eight named tables, but there is no transport/job/retry/cron invocation sentinel; a read that calls an external service without persisting could pass. Browser coverage still performs a single Tab from an unspecified initial focus and checks only that focus is not `body`; it does not establish that the actual page controls/pagination are reachable or visibly focused. It also does not assert the required empty/error states or concrete A7 residual-gap message. Correction: add deterministic fail-on-call adapters or network/process sentinels for forbidden invocations, and assert focus order/visibility on actual controls plus empty/error/gap UI at both viewports.

### Corrected RED evidence and verdict

I reran:

`php -l tests/Yii2/yii2_integration_status_001_test.php`

Result: syntax check GREEN; this cannot detect the malformed SQL string.

I then reran the package-selected focused command:

`php tests/Yii2/yii2_integration_status_001_test.php`

It deterministically fails at line 7 with `INTENDED_RED: integration status controller absent` (expected `true`, actual `false`). That is the intended first failure today, but it masks the line 12 fixture failure and leaves every corrected HTTP/browser assertion unexecuted. Consequently it is not sufficient executable RED evidence for this corrected matrix.

`CHANGES_REQUESTED`

Gate 4 remains blocked. Correct the fixture setup and the bounded remaining A1–A7 gaps above, capture a fresh exact-source RED whose setup is known runnable and whose failure is sensitive to the missing production behavior, then resubmit the complete candidate for independent Gate 3 review. CI, implementation, PR, and deployment remain `UNKNOWN` and are not treated as GREEN or authorization.

---

## Gate 3 third review — 2026-09-24

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015642Z-0dd7e8462e/package.json`.
- Exact reviewed source: reconstructible dirty snapshot over base `b1542f92009b8dc4216a36962ff38a51e0b6c388`, source digest `b41c111eb4689ec4b45e868321c7f8efdb99c4cd9bebbe97f404e4ad4e29df78`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015642Z-0dd7e8462e/snapshot/source.patch`, SHA-256 `0e2691d124db0097f3d809ccd44ae28b74743c4ed44bf7c81a4dd5efdd86b934` (matches `snapshot/manifest.json`).
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015642Z-0dd7e8462e/verification-plan.json`, package-declared SHA-256 `041ff0523003ac313474ad757cdc62f9cd97783272d08b336251a5f6475d9515`.
- Reviewer independence remains unchanged.

### Resolution of the preceding correction findings

- The malformed ERP INSERT is fixed; its failed row is now syntactically valid SQL.
- The A1 blocked-actor omission is fixed with a second authenticated administrator whose durable activation state is changed to `blocked` before a fresh request. The test requires denial and absence of a protected failure canary.
- Combined-page output now at least requires the two unaffected parameter names/values to survive while the ERP page changes. This is a useful witness, but it does not resolve list membership, bounds, normalization, or the two unpopulated lists.
- A runtime included-file denylist was added, but it is inspected after the blocked request. `UserAccessFixture` overwrites `includes.json` in every request shutdown, so the trace represents the blocked/early-denied request, not the earlier authorized GET that actually composes the read model. It therefore does not establish A4 load closure for the allowed seam.

### Remaining findings

1. **HIGH — A2's never-run/unavailable/error-safety scenarios remain untested.** Locations: stable contract A2/A6; delta scenarios `Источник не запускался или недоступен` and `Ошибка reader`; `tests/Yii2/yii2_integration_status_001_test.php:9-18`. Both integrations are always seeded with a completed run and a later failed run. No request observes an integration with no runs, an absent/unreadable projection, or a controlled reader exception. There are still no hostile markup, exception, stack, DSN, URL, token, or secret canaries. Thus an implementation may label never-run as success, collapse read failure into successful empty data, leak a reader exception, or fail the page and still satisfy every reachable assertion. Correction: add isolated HTTP scenarios for never-run and unavailable/read-error, with distinct protected canaries and exact safe state/body assertions; seed a malicious value in an allowlisted display field and assert escaped text/non-execution.

2. **HIGH — A3 is still not a complete behavioral test of the three independent bounded lists.** Locations: stable contract A3/A5; `tests/Yii2/yii2_integration_status_001_test.php:14,20`; `tests/Yii2/integration_status_browser.mjs:3`. Only ERP diagnostics contain rows. Workforce `missing_from_delivery` and dead jobs remain empty, so their filtering, fixed bounds, totals, ordering, rendering, and independent pagination may be absent or wrong without failure. Even for ERP, page 2 is checked only for HTTP 200, and invalid/out-of-range values are checked only for HTTP 200; exact row membership, maximum page size, page bounds, normalization, stable order, and total are not asserted. The two preserved parameter substrings may occur anywhere in the response rather than on the relevant pagination links. Correction: seed over-limit qualifying and nonqualifying records for all three lists, derive exact expected rows/count/order independently, assert each page and invalid/out-of-range normalization, and inspect/follow relevant links to prove all other parameters survive.

3. **MEDIUM — A4 authorized-read load closure and A5 browser semantics remain weak.** Locations: `tests/Yii2/yii2_integration_status_001_test.php:22-24`; `tests/Yii2/UserAccessFixture.php:55-65`; `tests/Yii2/integration_status_browser.mjs:3`. Because `includes.json` is overwritten per request and the last request before inspection is the blocked-user request, the new denylist can pass even if the authorized admin GET loads or invokes a forbidden transport/job owner. Capture and inspect the trace immediately after an authorized GET (or use deterministic fail-on-call adapters/network/process sentinels). Browser coverage still uses a single unspecified Tab and never follows pagination, validates focus visibility/order on actual controls, exercises empty/error output, or checks the concrete residual-gap presentation.

### RED evidence and verdict

`php -l tests/Yii2/yii2_integration_status_001_test.php` is GREEN. The package-selected `php tests/Yii2/yii2_integration_status_001_test.php` still deterministically fails first at line 7 with `INTENDED_RED: integration status controller absent` (expected `true`, actual `false`). The setup correction removes the known SQL blocker, but this early guard still means the newly added post-implementation assertions have not executed in retained RED evidence.

`CHANGES_REQUESTED`

Gate 4 remains blocked on the three bounded findings above. The third candidate fixes the setup and actor defects but is not yet sufficient for the stable contract's explicit first-slice state, safety, and three-list acceptance matrix. Submit one complete corrected exact-source package with fresh intended RED for independent rereview. CI, implementation, PR, and deployment remain `UNKNOWN`.

---

## Gate 3 fourth review — 2026-09-24

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015903Z-9039e509ba/package.json`.
- Exact reviewed source: reconstructible dirty snapshot over base `b1542f92009b8dc4216a36962ff38a51e0b6c388`, source digest `98266d8b1a1f8caae2e2c94abb11ac989cc57422b3b934571688635963e6b71e`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015903Z-9039e509ba/snapshot/source.patch`, SHA-256 `5188a0e203b54bb7136edc432f0a830c69dbe77cc2f25f54ff10814c0f89b52a` (matches `snapshot/manifest.json`).
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015903Z-9039e509ba/verification-plan.json`, package-declared SHA-256 `99cb6fa73db4a11d45b6871fd974830e691d7ee76541bbd781b41f83f993a8a0`.
- Reviewer independence remains unchanged.

### Resolved coverage

The fourth candidate materially resolves most of the previous matrix gap. It seeds more than one page of workforce missing rows, ERP missing/ambiguous diagnostics, and dead jobs; requires distinct second-page evidence from all three; preserves all three page parameters; and launches a browser that sees three tables, focuses a real pagination control, follows its server-generated destination, and checks both desktop and narrow layouts. It separately observes failed-after-success with a zero-count success, ERP never-run, and an ERP read failure caused by a temporarily unavailable table. The unavailable response must use the stable safe label and omit table/SQL/stack canaries, and the table is restored in `finally`. These are appropriate public-seam, isolated fixture witnesses.

### Remaining findings

1. **HIGH — the normative persisted-markup escaping scenario is still absent.** Locations: delta requirement `Вредоносное сохранённое значение`; stable A5/A6; `tests/Yii2/yii2_integration_status_001_test.php:14-16,20,26`; `tests/Yii2/integration_status_browser.mjs:3`. Every seeded display value is benign. The browser assertion that the resulting page contains no inline script cannot detect an implementation that renders persisted fields raw, because no fixture contains markup. A view may output workforce FIO, job failure code/type, or another allowlisted value without encoding and every current assertion will pass. Correction: put a unique HTML/script canary in a persisted field that the contract permits the page to display, require the encoded/text form in HTTP/DOM, require zero execution/created attacker element, and require the raw executable form absent.

2. **MEDIUM — invalid/out-of-range normalization is not tied to the targeted list, and only ERP parameters are malformed.** Locations: delta scenarios `Независимая пагинация` and `Несуществующая страница`; `tests/Yii2/yii2_integration_status_001_test.php:22`. The assertion searches the whole response for generic `Страница 1`; workforce or jobs pagination can satisfy it even if `equipmentPage` is mishandled. No invalid/negative/huge `workforcePage` or `jobsPage` request is sent. Second-page evidence is useful, but exact totals, maximum list size, and page-specific bounds/normalization are still not asserted. Correction: identify each list/paginator semantically or by its parameterized links, assert its exact total/page bounds and page-one membership after invalid input, and apply the malformed/out-of-range table to all three independent parameters.

3. **MEDIUM — the load-closure assertion still observes a denied request, not the authorized GET.** Locations: `tests/Yii2/yii2_integration_status_001_test.php:27-28`; `tests/Yii2/UserAccessFixture.php:55-65`. The router shutdown rewrites `includes.json` for every request. Immediately before reading it, the test performs the blocked-user request, which can terminate before composing the integration reader. Therefore the denylist passes even if the authorized GET or browser request loads a forbidden transport/job owner. Correction: perform a fresh authorized GET immediately before reading the trace (and assert its 200 response), or preserve per-request traces; then apply the denylist to that exact authorized request. Durable before/after row equality remains a valid complementary no-mutation witness.

### RED evidence and verdict

`php -l tests/Yii2/yii2_integration_status_001_test.php` is GREEN. `php tests/Yii2/yii2_integration_status_001_test.php` deterministically fails first at line 7 with `INTENDED_RED: integration status controller absent` (expected `true`, actual `false`). This remains an intended missing-production RED, but the corrected downstream fixture/browser assertions necessarily remain unexecuted until Gate 4.

`CHANGES_REQUESTED`

Gate 4 remains blocked on the three narrow, foreseeable test gaps above. Preserve the now-complete three-list and state/error fixtures, add the missing hostile-value oracle, make invalid normalization list-specific for every parameter, and bind load closure to an authorized request before preparing the next exact-source rereview. CI, implementation, PR, and deployment remain `UNKNOWN`.

---

## Gate 3 fifth and final review — 2026-09-24

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T020046Z-5b284d7a39/package.json`.
- Exact reviewed source: reconstructible dirty snapshot over base `b1542f92009b8dc4216a36962ff38a51e0b6c388`, source digest `b1fc28da4bb99ffc34a53245bcd6d34b629bd1e045b18016e252ece16661cdf3`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T020046Z-5b284d7a39/snapshot/source.patch`, SHA-256 `f47ab0b5b3eee9fbe54d8a0882591ae2bd6dc568b34d5483c4fd1a5f83ef82ae` (matches `snapshot/manifest.json`).
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T020046Z-5b284d7a39/verification-plan.json`, package-declared SHA-256 `f834013171e7bbc665c7ab081a137c462f9a381f8f6ae15064dad367eb036402`.
- Reviewer independence remains unchanged; this reviewer authored none of the approved contract, tests, fixtures, OpenSpec artifacts, or verification input.

### Final correction assessment

No findings.

All three fourth-review findings are resolved:

- Workforce fixture row 1 now contains a unique executable markup canary. The authorized HTTP response must contain its exact HTML-encoded text and must not contain the raw script element. The browser also requires no injected inline script. This is sensitive to a raw persisted-value rendering regression at the declared public seam.
- Negative, nonnumeric, and oversized values are sent separately for `equipmentPage`, `workforcePage`, and `jobsPage`. Each response must remain successful and must not preserve the invalid parameter/value, while the retained populated-page and second-page assertions establish the surrounding server-pagination behavior for all three lists.
- `authorizedIncludes` is captured immediately after the successful admin GET, before HEAD, rejected-method, browser, unavailable, or blocked requests can overwrite the per-request trace. The forbidden transport/job owner denylist is therefore bound to the exact allowed read, complementing the complete durable before/after inventory.

The complete A1–A7 suite now exercises the real Yii HTTP seam for admin, non-admin, guest, and a current blocked administrator; GET, HEAD, and rejected methods; failed-after-success with independently seeded zero-count successes; never-run and unavailable/error-safe output; raw-receipt and sensitive error redaction; all three over-limit diagnostic/job lists with distinct second-page evidence and preserved independent parameters; repeated stable reads with no changes to integrations/jobs/outbox; persisted-value escaping; desktop/narrow rendering; three tables; real pagination focus and navigation; and authorized-request load closure. Expected values derive from isolated durable fixtures rather than planned production internals. The tests remain deterministic and do not touch the working stand or external systems.

`php -l tests/Yii2/yii2_integration_status_001_test.php` is GREEN. The package-selected focused command `php tests/Yii2/yii2_integration_status_001_test.php` deterministically fails first at line 7 with `INTENDED_RED: integration status controller absent` (expected `true`, actual `false`). This is the intended missing-production RED, not a setup failure. Later assertions are designed for Gate 4 GREEN and were reviewed statically against the runnable shared fixture and schema.

### Final verdict

`APPROVED`

Gate 3 passes for exact source `b1fc28da4bb99ffc34a53245bcd6d34b629bd1e045b18016e252ece16661cdf3`. Gate 4 may proceed only against this reviewed contract/test package. Any later expectation or test change requires a fresh exact-source Gate 2/3 cycle. Implementation, final review, CI, PR, merge, and deployment remain `UNKNOWN`; this approval does not authorize or imply them.

---

## Gate 3 test-delta correction review — 2026-09-24

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T020740Z-41cd66239e/package.json`.
- Exact source containing the test correction and paused, unreviewed production: reconstructible dirty snapshot over base `b1542f92009b8dc4216a36962ff38a51e0b6c388`, source digest `0e887e352d95780b956c6d0fc65be7948bf502c58309e78db758520c7771a020`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T020740Z-41cd66239e/snapshot/source.patch`, SHA-256 `9b11d51645a25a0051509bc0ffb2ced5d1cebb1458d267345c9bf6e71445de18` (matches `snapshot/manifest.json`).
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T020740Z-41cd66239e/verification-plan.json`, package-declared SHA-256 `c6fa331672e08ba2f9cc7d6d567d1d6ac80f9c037e685c4807d85a2d327756fe`.
- Review scope is only the three corrections in `tests/Yii2/yii2_integration_status_001_test.php` relative to the previously approved exact test source `b1fc28da4bb99ffc34a53245bcd6d34b629bd1e045b18016e252ece16661cdf3`. Production files present in this snapshot were not reviewed and receive no approval here.

### Assessment

No findings.

The corrected durable inventory replaces nonexistent `fm2_outbox` with the actual append-only delivery tables `fm2_outbox_intents` and `fm2_outbox_attempt_events`. This strengthens rather than weakens A4: every GET/HEAD/rejected-method request before the comparison must leave both outbox intent state and attempt history unchanged, alongside integration and job tables.

`UserAccessFixture::login()` returns the full HTTP response array. Reading `['status']` fixes the test API misuse while retaining the exact expected 303 outcome for admin, second administrator, and ordinary-user sessions. Actor authorization sensitivity is unchanged.

The visible date expectations now use `21.09.2026`, `20.09.2026`, and `22.09.2026`. This matches the established active Yii Russian presentation convention (`d.m.Y`, with optional time) and remains independently derived from the three seeded attempt/success instants. The assertions still distinguish latest attempt from earlier success for both sources; no timestamp or state requirement was relaxed.

`php -l tests/Yii2/yii2_integration_status_001_test.php` is GREEN. With paused production present, the focused command progresses through fixture creation, all three logins, authorization, and the corrected date assertions, then fails at the still-required A7 label `Не регистрируется`. This confirms the three corrections are executable and preserve sensitivity; the observed failure is a production-conformance matter outside this test-delta review, not a fixture/setup failure or approval of production.

### Delta verdict

`APPROVED`

Gate 3 approval extends to this test delta at exact source `0e887e352d95780b956c6d0fc65be7948bf502c58309e78db758520c7771a020`. Implementation may resume against the corrected expectations. The focused test must become fully GREEN without changing an approved expectation before independent final review. Production, final review, CI, PR, merge, and deployment remain `UNKNOWN`.

---

## Gate 3 delta review — repeat-read CSRF normalization — 2026-09-24

- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T021650Z-50f3f3dc3e/package.json`.
- Exact source: reconstructible dirty snapshot over base `b1542f92009b8dc4216a36962ff38a51e0b6c388`, source digest `b3415a223f8a2a88c39c8a942550a5378601f50d0bb82aedb1787178fdf1a04b`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T021650Z-50f3f3dc3e/snapshot/source.patch`, SHA-256 `01331cff08337db0a3cfa2f089d89ae8e5c3211472a2b8d4d040b2c4446a85b9` (matches `snapshot/manifest.json`).
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T021650Z-50f3f3dc3e/verification-plan.json`, package-declared SHA-256 `fe3514228153ddaac508fe5ba95ec543ed10421600834e5c8c61f86d132bfb49`.
- Review scope is the single repeat-read expectation change in `tests/Yii2/yii2_integration_status_001_test.php:23`, required by Gate 5 finding 1. Production present in the snapshot is outside this Gate 3 delta decision.

### Assessment

No findings.

The removed assertion required byte-identical full HTML across two GET responses. That was not a normative freshness or replay outcome: the shared shell contains Yii's intentionally masked CSRF value, whose wire representation may vary while representing the same valid token. The expectation encouraged the production view to persist and substitute an integration-specific CSRF representation on a nominally read-only GET.

The replacement retains the material A4/replay sensitivity:

- the complete workforce/ERP/jobs/outbox durable inventory is still compared with the pre-request snapshot after the actor, method, pagination, invalid-input, and initial authorized-read matrix;
- the second authorized GET must return HTTP 200;
- the repeat body must contain exactly the same multiplicity of each independently seeded latest-attempt/latest-success date (`21.09.2026`, `20.09.2026`, and `22.09.2026`) as the initial body.

Together with the existing explicit timestamps, failure codes, zero-count success, pagination content, and raw/hostile-value checks on the initial response, this proves the repeated read continues to present the same durable integration facts without prescribing volatile security-token bytes. No acceptance behavior is narrowed.

`php -l tests/Yii2/yii2_integration_status_001_test.php` is GREEN, and the focused command `php tests/Yii2/yii2_integration_status_001_test.php` is GREEN at this exact source.

### Delta verdict

`APPROVED`

Gate 3 approves this one-line test correction for exact source `b3415a223f8a2a88c39c8a942550a5378601f50d0bb82aedb1787178fdf1a04b`. Implementation may remove the integration-specific session/CSRF stabilization and address the remaining Gate 5 findings without restoring byte-identical HTML. Any further expectation change requires a fresh Gate 2/3 delta review. Production and Gate 5 remain unapproved until independent correction rereview.

---

## Gate 3 delta review — Gate 5 error/empty/resource regressions — 2026-09-24

- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T022344Z-fdfcf98bb8/package.json`.
- Exact source: reconstructible dirty snapshot over base `b1542f92009b8dc4216a36962ff38a51e0b6c388`, source digest `7ea13c5fca2d627fea16722d27b9419d5b615ec243d65f75ba201e157f65a368`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T022344Z-fdfcf98bb8/snapshot/source.patch`, SHA-256 `199e6e69d2ea361ce8499445f081a4c9b19d0777d09c5df6c7b10ba88636e323` (matches `snapshot/manifest.json`).
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T022344Z-fdfcf98bb8/verification-plan.json`, package-declared SHA-256 `47c9d7cca9d0d562735fb435cc5178d554eb85bc4f086bee590fb135c4959fc3`.
- Review scope is the focused-test delta added for the sole remaining Gate 5 correction finding. Production remains outside this Gate 3 decision.

### Assessment

No findings.

The new behavioral empty-state scenario removes every qualifying row from the three independently seeded lists: all workforce rows with `missing_from_delivery`, all ERP diagnostic rows, and all dead jobs. A fresh authenticated GET must then contain the distinct source-specific empty messages for workforce, ERP diagnostics, and jobs. Removing any one of the three corrected `shlz-empty-state` branches or confusing empty success with an error now fails the test.

The jobs-unavailable scenario renames the real `fm2_jobs` table to a name containing a protected `private_payload_token` canary, requests the public HTTP seam, and requires the jobs-specific `Данные заданий недоступны` outcome while forbidding the canary. The table is restored in `finally`. This is sensitive both to loss of the `available=false` branch and to raw database exception/table leakage, and it is distinct from the preceding successful-empty request.

The lifecycle assertion is an intentionally narrow structural ownership witness for a failure that is impractical to inject through native `mysqli`: it requires, in order, `try`, `set_charset`, a `Throwable` variable, `close`, rethrow of that same captured error, and only then return of the initialized connection. It fails if close or rethrow is removed, if a different error is substituted, or if return precedes cleanup ownership. The existing public HTTP matrix continues to exercise successful connection ownership and outer-finally closure; exact native handle counting remains unnecessary.

These additions preserve all previously approved A1–A7 expectations and add sensitivity only for the exact Gate 5-returned regressions. Fixture mutations remain confined to the isolated database and restore renamed schema in `finally`.

`php -l tests/Yii2/yii2_integration_status_001_test.php` is GREEN, and `php tests/Yii2/yii2_integration_status_001_test.php` is GREEN at this exact source.

### Delta verdict

`APPROVED`

Gate 3 approves the Gate 5 correction test delta for exact source `7ea13c5fca2d627fea16722d27b9419d5b615ec243d65f75ba201e157f65a368`. The complete candidate may return to independent Gate 5 rereview after refreshed exact-source evidence. Any further test expectation change requires another Gate 2/3 delta decision; production is not approved by this record.
