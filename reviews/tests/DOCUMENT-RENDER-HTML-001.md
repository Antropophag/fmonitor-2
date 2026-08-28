# Test review: DOCUMENT-RENDER-HTML-001

- Reviewer: `Codex agent /root/migration_test_review` (independent; did not author the specification, test, fixture, or production implementation)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/DOCUMENT-RENDER-HTML-001.md`](../../specs/DOCUMENT-RENDER-HTML-001.md), version `0.2`, `APPROVED 2026-08-28`
- Public seam: `InstallationProcess::prepareAssignmentOrder(...)` and `::getInstallationObjectProcess(...)`; production renderer output observed through a recording delegate boundary reached only by the public command
- Red command: `php tests/InstallationProcess/document_render_html_001_test.php`
- Initial verdict: `CHANGES_REQUESTED`
- Current verdict: `APPROVED`

## v0.2 Gate 5 restart review

The new secondary-adapter boundary and fail-closed matrix are approved. The original benign public-command tracer runs first and reaches all of its exact bytes, metadata, full projection, event, and security assertions. The focused run then fails on the first newly specified corrupt input:

```text
PHP Fatal error:  Uncaught TestFailure: zero version must fail with the approved exception type.
Expected: 'InvalidArgumentException'
Actual: NULL in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/document_render_html_001_test.php(241): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`.

- **Approved seam and traceability:** v0.2 explicitly approves `ProductionHtmlAssignmentOrderRenderer::renderAssignmentOrder(documentInput)` as the secondary adapter seam for corrupt internal inputs that the validating process command cannot construct. The direct cases do not claim UI/HTTP access or bypass the process for normal behavior. All five mandatory examples A–E are present.
- **Sensitive shape matrix:** independent mutations cover version presence/type/positivity/path-like text; order-date type and calendar validity; exact supported organization; object presence/type plus every required object field across missing/blank/type/calendar/format partitions; installer list presence/listness/exact cardinality/element type plus every required installer field across ID/type/missing/blank partitions; and engineer presence/type plus every required engineer field. Gapped numeric keys and two installers specifically distinguish `array` from exact singleton `list`.
- **No tautology:** `$validDocumentInput` is an independently authored approved literal. Each named closure changes one relevant fact; expected exception class/message are fixed specification literals, not production constants or output. Cases do not reproduce the production validation algorithm.
- **Exact safe failure:** each invocation starts with a fresh value-copy, initializes result to `null`, catches any throwable, then requires exactly `InvalidArgumentException`, exactly `Invalid assignment order document input.`, and no returned empty/partial artifact list. Notices, type errors, leaked values/details, normalization, unsupported templates, or unsafe filenames fail.
- **No overreach:** the matrix asserts only the single approved adapter exception contract. It does not invent process rejection shapes, HTTP behavior, logging, storage cleanup, malicious-text output bytes, brigade rendering, or filesystem behavior. Extra input fields remain untested/permitted as specified.
- **Benign regression retained:** the complete v0.1 public command/projection test remains in the same focused execution before invalid calls, so adding guards cannot change the exact two valid HTML documents, filenames/media types, sizes/hashes, event, or inherited process facts.
- **Determinism/isolation:** all cases are local arrays and a stateless renderer in one PHP process; no clock, locale, DB, filesystem, network, randomness, or cross-test state participates. Mutation closures receive fresh copies, so one corrupt case cannot contaminate another.

The RED is for the intended missing validation: current production returns artifacts for zero version, leaving `$caught = null`. It is not caused by the already implemented benign renderer path.

**Fresh Gate 3 verdict for v0.2: `APPROVED`. Gate 4 may add only the reviewed fail-closed boundary validation, then Gate 5 must restart independently.**

## Re-review after Gate 2 correction

Both blocking contradictions are resolved.

Each independently authored nowdoc is now followed by an explicit `"\n"`. The resulting expected byte strings are exactly `1093` and `1262` bytes and match the approved SHA-256 literals `682749...f4928` and `da33d...e3ac`. Because the nowdoc itself ends at `</html>`, this construction supplies exactly one terminal LF, no CR, and no BOM, and exact boundary equality diagnoses any byte deviation.

The revised test also compares the entire public process projection. Its `events` value is an exact one-element list, so a renderer-specific extra event, byte-bearing event, duplicate preparation event, or reordered history cannot pass. Full equality additionally protects every inherited snapshot, assignment, gate, task, version/status, and artifact metadata fact promised by section 7.

Fresh RED:

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\ProductionHtmlAssignmentOrderRenderer" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/document_render_html_001_test.php:78
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/document_render_html_001_test.php on line 78
```

Exit code: `255`.

The failure remains solely at the absent named production renderer. Public-command-only invocation, recording boundary, exact independent HTML bytes and metadata, benign-output security checks, no-byte public artifacts/event, deterministic fixtures, and honest HTML-only scope all remain sound.

**Gate 3 verdict: `APPROVED`. Gate 4 may proceed without changing the reviewed expectations.**

## Captured red result

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\ProductionHtmlAssignmentOrderRenderer" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/document_render_html_001_test.php:76
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/document_render_html_001_test.php on line 76
```

Exit code: `255`.

Bootstrap and the existing process/support environment load before the intended absent production renderer class is instantiated. The RED is directly attributable to missing slice behavior, not a malformed fixture or inherited command failure.

## Findings

- **Seam and renderer boundary:** the test never calls the renderer directly. It injects the named production renderer as the sole delegate, invokes only public preparation, and records exactly what the command received back from that delegate. This is an appropriate collaboration observation for the exact bytes contract and remains sensitive to the real public command wiring.
- **Independent HTML literals:** both documents are literal transcriptions of specification sections 5–6, not generated from production templates, renderer output, hashes, or a duplicate formatting algorithm. Exact equality is sensitive to field mapping, formatting, CSS/attribute ordering, whitespace, encoding content, filename/media type, and artifact order.
- **Escaping/security scope:** for this approved benign example, exact bytes plus the explicit forbidden-markup regex reject injected script tags, remote URLs, external links/styles, iframe/form tags, and executable event attributes in returned output. The spec honestly defers a malicious dynamic-input escaping tracer; therefore this test does not overclaim sensitivity to every `htmlspecialchars` branch. Gate 5 must still inspect the invariant until that separate slice exists.
- **Artifact metadata independence:** expected sizes and SHA-256 values are fixed spec literals rather than computed from returned bytes. The public process must calculate metadata from renderer bytes, so a byte difference cannot be hidden by adapting the test expectation. The reviewed numeric/hash pairs independently match the specified strings **with** one final LF: `1093 / 682749...f4928` and `1262 / da33d...e3ac`.
- **Resolved final-LF contradiction:** explicit LF concatenation aligns exact boundary bytes with the independently fixed size/hash metadata and enforces the approved terminal byte.
- **Resolved event/history observability:** complete projection equality includes an exact singleton events list and all inherited process/order facts.
- **Public metadata and no bytes:** exact artifact objects contain only type/filename/mediaType/size/hash, so bytes leaked into artifact projection fail. Exact event-object equality excludes bytes and extra fields from the preparation payload itself. After the event-list correction, extra events will also be observable.
- **Determinism:** all source snapshots, date/time, expected HTML, CSS, Unicode punctuation, hashes, and process facts are fixed in a fresh in-memory environment. No locale, filesystem, network, database ordering, wall clock, or randomness participates.
- **Honest HTML scope:** filenames and media types are strictly `.html` and `text/html`; no PDF/DOCX, signing, byte storage/download, regeneration, browser matrix, or remote resources are claimed. Deferred malicious input and storage/download seams are explicit.
- **Inherited projection coverage:** the revised strict full projection proves all section 7 facts directly, including snapshots, assignments, gates/tasks, singleton history, and metadata without bytes.

## Previously required changes (resolved)

1. Completed: both expected documents explicitly append one LF and retain independent size/hash literals.
2. Completed: the exact complete events list is asserted.
3. Completed: the full inherited public projection is asserted with independent literals.
4. Completed: all prior strengths and intended RED remain and were independently rerun.
