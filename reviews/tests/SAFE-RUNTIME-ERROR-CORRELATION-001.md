# Test review: SAFE-RUNTIME-ERROR-CORRELATION-001

- Reviewer: independent Gate 3 reviewer (gpt-5.6-sol / low)
- Test author: root agent
- Reviewed source: base `e3b39e59b7ba75a3af7dd3d22871c5c9f25d44e9` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T071549Z-1009f69ebd/snapshot/source.patch`, SHA-256 `295779f014b6d25f888e72e73a88a4757bcade051f2173bf553a520d98df1be6`; candidate source `801af96bba547b2d049c85f940fe7213748102c900c0c6d933de5da0a59cf80b`
- Agreed review scope / prior findings disposition (for rereview): initial Gate 3 review; no prior findings
- Specification: `specs/SAFE-RUNTIME-ERROR-CORRELATION-001.md` and `openspec/changes/safe-runtime-error-correlation/specs/runtime/safe-error-correlation/spec.md`
- Public seam: canonical wire HTTP response plus bytes actually appended to the runtime log
- Red command and intended failure: `php tests/Runtime/yii2_safe_error_correlation_001_test.php`; retained evidence record `1789888515760586000-098a638794694dcaae3d891dc9b357ce.json` exits 255 because the bootstrap configuration 503 lacks `X-FMonitor-Error-ID`. This is an intended missing-behavior failure, not a setup failure.
- Initial verdict: `CHANGES_REQUESTED`

## Findings

1. **High — acceptance example 8 and the adjacent authorization/admission contract are not covered.** `tests/Runtime/yii2_safe_error_correlation_001_test.php:88-91` exercises only one success and one 404, while the normative contract at `specs/SAFE-RUNTIME-ERROR-CORRELATION-001.md:33,44` explicitly requires representative 400/403/404/405/409/413/415 behavior, including authorization/RBAC/CSRF decisions, to retain exact prior wire behavior and emit no failure record. A regression that adds IDs/log records to 400, 403, 405, 409, 413, or 415—or changes their status/body/headers—would pass this test. Add public-HTTP cases covering the enumerated representative statuses and relevant admission decisions, asserting independently fixed prior status/body/material headers, no error-ID, and no new diagnostic record.

2. **High — logger-failure coverage does not prove command-at-most-once, external-effect safety, or unchanged domain facts.** The only broken-sink case at `tests/Runtime/yii2_safe_error_correlation_001_test.php:75-77` is a pre-Yii bootstrap configuration failure, where no business command or external effect is invoked. The controller injections at lines 93-96 are read paths and use a functioning sink. Consequently the test cannot detect a logger-failure implementation that retries a controller command, changes an already accepted result, or writes domain facts, despite the normative requirements at `specs/SAFE-RUNTIME-ERROR-CORRELATION-001.md:31,42,48`. Add a controlled selected-controller command failure with an observable invocation counter/fact snapshot and a failing sink, and assert one invocation, unchanged facts/history, and the unchanged safe 503 plus server-owned ID.

3. **High — compatibility assertions are incomplete on the global-handler and controller paths.** Bootstrap cases use `srcSafe`, but the global handler at `tests/Runtime/yii2_safe_error_correlation_001_test.php:79-86` checks only status, unsafe-header removal, ID, and record; controller cases at lines 105-108 check only status, ID, record classification, and canary absence. They do not pin the existing body/reason, `Retry-After`, or material security headers required by `specs/SAFE-RUNTIME-ERROR-CORRELATION-001.md:19,37-43`. In particular, the storage controller example requires the prior 503 envelope, and the accepted-operation path requires preservation of the unknown-result message. Add independently expected wire assertions per selected boundary (not a single bootstrap-only helper assumption), including the distinct HTML/plain/JSON envelopes and relevant headers.

4. **Medium — the privacy scenario does not place canaries in all contractually named inputs.** `tests/Runtime/yii2_safe_error_correlation_001_test.php:63-72,93-108` covers environment/DSN-password, cookie, query, client header, and exception-message canaries, but it does not exercise request body, SQL/parameters, or filename/document metadata named by the delta scenario and normative privacy rule (`specs/SAFE-RUNTIME-ERROR-CORRELATION-001.md:27,40`). Exact record keys are a strong structural check, but the test also promises all-input fault injection and currently cannot catch accidental serialization of those unexercised sources. Add reachable public-seam cases (or split, explicit boundary cases) that inject distinct canaries into each applicable named source and assert absence from both wire output and the exact physical diagnostic line.

The test otherwise uses the correct public seams, derives record shape and classifications from the contract, verifies exact ID correlation and single physical emission, exercises bootstrap without Yii/DB, rejects a client-owned ID, and has valid INTENDED_RED evidence for the first missing behavior. The current RED stops at the first bootstrap assertion, so downstream viability will need fresh complete evidence after the blocking coverage is added.

## Required changes

- Cover every representative success/4xx status and admission decision promised by acceptance example 8 with exact prior wire expectations and no-record assertions.
- Exercise a failing logger on a selected command/effect path and prove at-most-once execution plus unchanged facts/history.
- Pin prior body/reason and applicable security/`Retry-After` headers for global-handler and each selected controller 503.
- Complete the privacy-input matrix for request body, SQL/parameters, and filename/document metadata, then retain a fresh INTENDED_RED result for the complete test.

## Correction round 1 — 2026-09-20

- Reviewed source: base `e3b39e59b7ba75a3af7dd3d22871c5c9f25d44e9` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T072026Z-5cc6d13b7e/snapshot/source.patch`, SHA-256 `339120e8430bd2f39d664efd34341fccf4dde5cb21b61e11910aa7f35fc5af9a`; candidate source `36ce445481c10ce5ccc454f1a763e630d658ccca90c1f0f522198e0edf324790`
- Fresh RED: record `1789888791733552000-0e64905224984e07bca123520acb494b.json`, container-backed exit 255 at the intended missing bootstrap error-ID assertion.
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Open.** Cases for 400/403/404/405/409/413/415 were added, but their no-record oracle compares the entire PHP server log before and after each HTTP request (`tests/Runtime/yii2_safe_error_correlation_001_test.php:64-68,99-106`). `PreopeningFixture::start()` directs the PHP built-in server stderr to that same file, and every request appends access/runtime lines. Therefore byte equality is not a viable diagnostic-record assertion. The raw 413 check is also insensitive to the mixed-case header because it searches the raw reply for lowercase `x-fmonitor-error-id`, and it does not pin the prior response body/material headers. Success still pins only status/no-ID rather than exact prior behavior.
2. **Open.** The proposed failing-sink command case at lines 112-120 does not reach the injected database command: it posts `action=<canary>`, and `ExecutionController::execute()` returns 400 for an unrecognized action before issuing a non-auth database query. It therefore cannot create the marker, injected exception, controller 503, or claimed command-at-most-once evidence. In addition, `ini_set("error_log", "/dev/full")` targets PHP's error log, while the design says initialized Yii uses its configured category logger; this does not establish that the selected Yii sink failed.
3. **Partially fixed, still open.** Global-handler security/body checks and controller security/body checks were added. The execution HTML check is only a substring assertion (`tests/Runtime/yii2_safe_error_correlation_001_test.php:124`), so arbitrary changes to the rest of the prior envelope still pass; the requirement and prior correction request call for independently fixed prior wire behavior.
4. **Open.** The new POST body contains fields named `originalFilename` and `sqlParameter`, but that request is an execution form, so those values are ignored and never become document metadata or an SQL parameter. No SQL containing a distinct canary is executed. This does not exercise the named privacy sources, and because the request returns 400 before fault injection, it produces no diagnostic line against which privacy can be checked.

### Complete remaining findings

1. **High — the downstream no-record checks are not viable with the selected factual log.** Lines 64-68 assert whole-file equality around requests, while the fixture captures PHP server stderr in that file. Replace this with parsing/counting only the structured `http_failure` diagnostic records (and, where useful, IDs), so ordinary server access lines cannot create false failures. Parse the raw 413 response case-insensitively and assert its exact prior body/material headers as well as absence of the ID and diagnostic record. Pin the success response's agreed material behavior rather than only status.
2. **High — the corrected logger-failure/at-most-once case is unreachable and does not fail the designed sink.** Drive a valid selected mutation far enough to invoke one controlled command/effect, arrange the actual Yii diagnostic sink to fail, inject the post-command failure at an explicit seam, then assert one invocation, unchanged or correctly retained facts/history according to the scenario, and the unchanged safe response. The fault mechanism must demonstrably target the same sink the implementation is expected to use.
3. **High — the privacy correction uses unused field names instead of the claimed sources.** Inject distinct canaries through actual request-body, SQL/parameter, and original filename/document-metadata paths that reach a selected failure and diagnostic record; assert each is absent from the wire response and exact physical record. An unused `sqlParameter` form key is not SQL evidence, and `originalFilename` on the execution route is not document metadata evidence.
4. **Medium — selected-controller compatibility remains under-specified for the HTML response.** Replace the execution-page substring check with an independently fixed expected envelope or an equivalent complete semantic structure that detects unintended changes outside the message text.

The fresh RED remains valid for the first missing feature, but because it stops at the bootstrap assertion it does not validate the reachability or setup of the newly added downstream cases. A corrected candidate needs fresh RED plus a viable way to demonstrate those downstream fault seams independently of the first assertion.

### Required changes

- Replace whole-server-log equality with structured diagnostic-record counting and make the 413/success assertions sensitive to exact prior behavior.
- Rebuild the logger-failure command case so it reaches a valid command exactly once and demonstrably fails the intended Yii sink.
- Put privacy canaries into actual SQL/parameters and original document metadata on reachable selected failure paths.
- Strengthen the execution HTML compatibility oracle to cover the complete agreed envelope.

## Correction round 2 — final Gate 3 decision — 2026-09-20

- Reviewed source: base `e3b39e59b7ba75a3af7dd3d22871c5c9f25d44e9` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T072600Z-5b68888d4f/snapshot/source.patch`, SHA-256 `e0d60a302afbe752620066c80c95e1b3e70d9d22203d1acde211148d2c35c5c3`; candidate source `b0554783cbb8013b9a185c49aedce1ec54266a5d3d4505d11dc4b829074cc61c`
- Fresh RED: record `1789889136897662000-9b4e319428c248b2b8322b3adeda49e8`, container-backed exit 255 at the intended missing bootstrap error-ID assertion; candidate and end source match.
- Verdict: `APPROVED`

### Prior findings disposition

1. **Fixed.** Success and representative 400/403/404/405/409/413/415 cases now assert independently expected material wire behavior, absence of the error-ID, and unchanged structured `http_failure` count. The raw 413 response parses headers case-insensitively and pins its body/content type without relying on whole-server-log equality.
2. **Fixed.** The design now explicitly declares the shared process `error_log()` sink for bootstrap and Yii paths. The broken-sink case drives a valid `open_confirmed` command shape to the injected database boundary, records exactly one attempt, targets `/dev/full`, expects no record for the returned ID, preserves the established 503/security/unknown-result response, and compares all domain facts/history before and after.
3. **Fixed.** Global, JSON, plain, and execution HTML 503 responses now have boundary-appropriate body/security/`Retry-After` checks. The HTML oracle pins the complete agreed error-page semantics: title, single unknown-result message, exact return target/label, and single no-retry guidance.
4. **Fixed.** Original upload now carries the canary in the actual PDF request body and parsed `originalFilename`; the login fault injects canaries as real bound credential-query parameters and includes SQL/parameters in the thrown exception. Wire response and exact allowlisted record are checked for absence of those values.

### Final findings

None. The candidate maps the full bounded acceptance contract through canonical HTTP and factual structured-log effects; checks server-owned correlation, exact one-record ownership, closed classification without message inference, privacy allowlisting, global sanitization, controller-specific compatibility, logger fail-open behavior, no retry/domain writes, and unchanged adjacent success/4xx behavior. Expected values are independently fixed, fault injection is isolated from production systems, and the retained RED fails for the missing feature rather than broken setup.

### Required changes

None.

## Authorized additional Gate 3 delta review — 2026-09-20

- Authorization/scope: owner-authorized one-time Gate 3 delta review required by the v1 harness GREEN-package gate. This is limited to corrected test-environment preparation, baseline/fault reachability, complete scenario viability, and preservation of approved expectations; it is not Gate 5 or a final production-code review.
- Reviewed source: base `2d6afb4c9bf050a054994536360e10d05d1b75df` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T081054Z-73fdc0a5c8/snapshot/source.patch`, SHA-256 `fc0a1530cab986f295d0fdd2dc24951904e1c7fa8194801e5f58e7dbf701ee79`; candidate source `e737d3ff9ef19b62b6f395cb1a3c8d64d79d26cee32f68a8773330dd68280df1`.
- Test delta lineage: approved historical source `b0554783cbb8013b9a185c49aedce1ec54266a5d3d4505d11dc4b829074cc61c`; historical container RED record `1789891766777353000-c9162e1d1e55449facca42334ae2455a`; current container GREEN record `1789891790114348000-bad606477b8d4a9180f641b9bacf9d38`; delta SHA-256 `fb95f4f0730cbcade05cf0b9d2d6c42c4b86c17a7e94576391e6c68f4dc96248`.
- Verdict: `APPROVED`

### Findings

None.

The correction prepares an isolated production-runtime storage tree before bootstrap testing and first proves that the prepared baseline reaches Yii without an error ID or diagnostic record. Configuration and database bootstrap faults therefore remain distinguishable from setup failure. The downstream cases now use reachable behavior rather than test-router substitutes: wrong-prefix execution failure, real original-upload metadata/body with unavailable storage, an authorized/opened checklist photo with a real storage obstruction, a valid `open_confirmed` attempt interrupted by a database trigger while the declared process sink is unavailable, and a visible SQL-trigger failure with privacy canary. The MyISAM attempt counter plus full fact snapshot establishes one command attempt and no retained domain mutation; trigger/table cleanup restores the fixture before comparison.

Expectation adjustments preserve or strengthen the approved contract. The 409 case now installs the exact capability needed to reach the existing domain rejection. Original 415 and 413 cases include valid CSRF and use ordinary HTTP, so they prove admission behavior rather than transport/setup behavior. Controller envelopes remain boundary-specific and pin status, material security headers, `Retry-After`, error ID, body semantics, category, exact correlated record, and canary exclusion. Replacing the synthetic checklist `unexpected` failure with a real `storage` failure improves factual classification coverage without removing unknown-exception coverage from execution/global paths.

The minimal production changes inspected solely for downstream-test consistency expose the expected public effects used by these tests: result-based 503 paths report once through the declared shared process sink, preserve existing envelopes, and use closed component/category values. No expectation was relaxed to mirror an implementation accident.

### Required changes

None.

## Owner-authorized Gate 3 regression delta for Gate 5 findings — 2026-09-20

- Authorization/scope: review only the regression-test delta for the two findings in `reviews/code/SAFE-RUNTIME-ERROR-CORRELATION-001.md`: an ordinary selection-portal `dependency_unavailable` result and assignment-order application `persistence_failure`. This is not a Gate 5 rereview and does not approve production code.
- Reviewed source: commit/base `48ce61755791eb4e598b4484d0161a8f8754c10e` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T082251Z-c2edd9783f/snapshot/source.patch`, SHA-256 `cfd05e54747203fc538b8a8ed14ecad4c03aa2d342a219700af72da4bb3bd83f`; candidate source `93d0ce5f59f5ab93cb5a5f23b7b90f7d1a1e5620c618edd14bf275b64ecba95a`.
- RED evidence: container record `1789892548321767000-eeeea7d05911435f935417a4963114ae`, exact candidate/end source match, exit 255 at `execution portal handled result one error ID header` (expected 1, actual 0).
- Verdict: `APPROVED`

### Findings

None.

The portal regression reaches the ordinary public `GET /pilot/objects/4512/execution` path with the real selection table temporarily unavailable. `MariaDbSelectionPortalQuery` converts that database fault to its stable `['status' => 'failed', 'reasonCode' => 'dependency_unavailable']` result, and `PreopeningController::domain()` preserves the established plain 503. The assertion requires the unchanged 503 body/security/`Retry-After`, one server-owned ID, exactly one factual record, `component=execution_controller`, and `category=dependency`. The retained RED fails specifically because that handled result lacks the ID; authentication, fixture setup, result conversion, and response rendering have already succeeded.

The application regression submits a valid public `action=apply` request with an accepted original and exact identifiers. A transaction trigger fails the real application insert, causing `MariaDbAssignmentOrderApplication` to return its stable `persistence_failure` result. The test requires the existing plain 503 contract, one correlated record with `category=database`, and a complete before/after fact/history equality check after trigger cleanup. `srcRecord()` also enforces one physical record by returned ID, so a correction that double-reports or merely changes the category cannot pass.

Together with the already approved thrown-exception, explicit opening-result, original-result/context, and checklist-result cases, this batch covers the known handled-503 class inside the agreed components: read projection dependency failure and mutation persistence failure are no longer represented only by thrown faults. Expectations remain specification-derived and do not prescribe implementation structure beyond the public HTTP/log seam and closed factual category mapping.

### Required changes

None.
