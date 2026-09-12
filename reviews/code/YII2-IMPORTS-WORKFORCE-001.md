# YII2-IMPORTS-WORKFORCE-001 — Gate 5 code review

- Date: 2026-09-12
- Reviewer: independent Codex reviewer `/root/gate5_case_import`; authored neither the normative specification/tests nor the implementation and did not perform Gate 3
- Executor package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T081906Z-7a282c6ee6/package.json`
- Exact source: base commit `adf30398c62ca4d9fc77b1d3ecdaed68a5a228bc` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T081906Z-7a282c6ee6/snapshot`, patch SHA-256 `15a560935fe297f4a8ac029bbc35ea3500c709ff67cab0a3cd2076e84aacf294`, harness source `0b8073e550303639878c36d269d241f039021a067f6a66d4bdcc22d1c338da2f`
- Gate 3 approved input: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T080804Z-d3126bddcf/snapshot`, base commit `adf30398c62ca4d9fc77b1d3ecdaed68a5a228bc`, patch SHA-256 `ca96cfb132c24cf20c35444225c0d47a9ac6f22c3e9b1fb1b988d6b6fc2f239e`
- Verification plan SHA-256: `b803bea02de81b873dee8cb8c3f10ff28b20f603598f66ee178c96bf2e53c583`

## Scope and source preservation

The complete Gate-3-to-executor delta contains only the two new production classes, the three launcher/configuration edits and OpenSpec task-state metadata. The normative specification and all approved tests are byte-identical to the Gate 3 input. The live candidate matches the executor package source digest. The ignored Composer `vendor` tree was copied from the established Yii2 jobs worktree solely as runtime setup and is excluded from source evidence. No production, specification or test file was changed during this review. Snapshot import, workforce sync, deployment and full local verification remain outside this review.

## Complete findings

1. **HIGH — a required mapped transport/redaction test is a real regression failure.** `tests/Yii2/yii2_imports_workforce_001_test.php:15` uses `auto_prepend_file=tests/Support/YiiCaseImportThrowingOwner.php` to declare a throwing replacement `PilotCaseImporter`, while `config/yii/console.php:6` unconditionally `require_once`s the production `PilotCaseImporter.php`. The resulting duplicate-class fatal exits `255` and emits the class name, file paths and stack trace instead of reaching `CaseImportConsole.php:26-28` and returning the required closed exit `70`, `{"ok":false,"reason":"IMPORT_FAILED"}` and empty stderr. This leaves A4's unknown-`Throwable` mapping/redaction assertion unproved and means the required focused plan is not GREEN. Correct the injectable fault seam without changing the accepted outcome, then return any test change through Gate 2 and an independent Gate 3 review before Gate 5.

2. **MEDIUM — production construction obscures a second owner site and defeats the approved ownership witness.** `app/YiiRuntime/CaseImportConsole.php:60-62` assigns `CaseImporter::class` to `$ownerClass` and dynamically constructs it. Together with the ordinary construction at line 25, this makes two construction sites while `tests/Yii2/yii2_imports_workforce_ownership_001_test.php:4` passes its claimed “Exactly one owner composition” check by counting the literal `PilotCaseImporter` only once. The dynamic indirection impairs static navigation and makes the source guard misleading. Use one explicit named importer construction boundary/factory for both the initial and reconciliation connections, and make the ownership assertion observe that actual boundary rather than a token count. Because correcting the sensitivity assertion changes an approved test, route it through Gate 2/Gate 3.

No other specification, authorization, durable-history, package/load, security, integration-boundary or maintainability finding was identified in the reviewed delta.

## Verification evidence

The first diagnostic run was retained as non-GREEN setup evidence: ignored `vendor` was absent, so runtime/package checks failed or cascaded while governance and ownership checks ran. After installing the ignored Composer tree, one same-source diagnostic rerun used `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local`. Eight of nine focused commands were GREEN; the transport test above was `REGRESSION_FAILURE`. Exact records:

- package/load: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789201372977403000-632bfcc4f139473fb4a52828a8158d9f.json` — GREEN
- architecture guard: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789201386651941000-a2f974a59bdd40cb9007900a3909bf62.json` — GREEN
- verification inventory: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789201406058650000-c50cdbb73e594f6c83bdcf7e596ea61f.json` — GREEN
- full direct/alias DB oracle: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789201436894281000-019f72e557364fd1ba5e499ef4bc250f.json` — GREEN
- closed transport/redaction: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789201534829793000-a781662442904a03b66f823f883c5f2d.json` — REGRESSION_FAILURE
- ownership witness: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789201537276270000-92878fc6f8bc4961bc02dfb504bf6652.json` — GREEN, but finding 2 shows insufficient sensitivity
- retained legacy oracle: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789201537934538000-f16daaff9a894ec685ca52b7529fea4e.json` — GREEN
- change-verification governance: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789201586851963000-76a7555bc7404ca184c180d77164e5c9.json` — GREEN
- runtime storage: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789201604225355000-2ce8947897bd4e82b96db63d548bc05f.json` — GREEN

No local `make test` or `make verify` was run. Exact-source full CI, PR, merge and deployment remain UNKNOWN and cannot compensate for the focused failure.

## Verdict

**REJECTED.** Gate 5 does not advance. Task 3.1 remains unchecked. Return the complete candidate through Gate 2/Gate 3 for the test correction and then to Gate 4/Gate 5 for a fresh exact-source package and GREEN focused evidence.

## Independent Gate 5 final rereview — 2026-09-12

- Reviewer: independent Codex reviewer `/root/gate5_case_import_final`; authored neither specification/tests nor implementation
- Corrected executor package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T092442Z-54f5d668de/package.json`
- Exact source: base commit `adf30398c62ca4d9fc77b1d3ecdaed68a5a228bc` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T092442Z-54f5d668de/snapshot`, patch SHA-256 `cb79759b5a46cb655ca2a738200ad726991ee3f50b251d7d8a183aeabd404e48`, harness source `1aaf1d04921c5030aadeff9bb0a262aed10efc32803fb313a657a30067709caa`
- Gate 3 approved input: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T080804Z-d3126bddcf/package.json`
- Verification plan SHA-256: `85706363e65133e06bf68b168578e339404ced0090b6e8dc4a8774a2beeffbe5`

### Prior findings and complete final findings

Both prior findings are resolved. `class_exists(CaseImporter::class)` preserves the injectable throwing owner and the transport test proves generic `Throwable` maps to closed exit `70`, redacted JSON and empty stderr. Normal import and unknown-commit reconciliation now both call one private `owner()` factory; that factory contains the sole explicit `new CaseImporter` construction site, restoring the approved single-composition witness without changing specification or tests.

Independent Standards and Spec axes found no remaining documented-standard, conformance, security, authorization, durable-history, package/load, scope or maintainability findings. The production delta remains limited to the case-import Yii adapter/controller, console registration and thin compatibility alias; snapshot import, workforce sync, deployment and local full-suite execution remain outside this slice.

### Exact-source focused evidence

All nine bounded focused commands are GREEN on harness source `1aaf1d04921c5030aadeff9bb0a262aed10efc32803fb313a657a30067709caa`, using the ignored Composer runtime and `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local`:

- package/load: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789205788567452000-42211c5bf66f42d387987692b652f125.json`
- architecture guard: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789205595588769000-5774bb22654b4fe080a3d793c82e12a6.json`
- verification inventory: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789205613112290000-82c85b1599e04759803797d2d619c324.json`
- full direct/alias DB oracle: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789205644788649000-137bee8a69dc4c51828665936363be52.json`
- closed transport/redaction: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789205712473052000-038c10cc5be941508c8ed7294200ae3e.json`
- ownership witness: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789205715306889000-a2d64daa2b9843b4b0e2083f1200269f.json`
- retained legacy oracle: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789205715920514000-1a4ec6c3e39a478f824f4ce268647442.json`
- change-verification governance: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789205750600185000-067fb7439bb6403da42bf21fc9f6d599.json`
- runtime storage: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789205766756317000-d07288588e0f421d9db206c6a53205d2.json`

The earlier missing-vendor failure and symlink-source-drift records remain retained as setup/UNKNOWN history and are not approval evidence. No local `make test` or `make verify` was run. Exact-source full CI, PR, merge and deployment remain `UNKNOWN` pending root publication.

### Final verdict

**APPROVED.** Gate 5 passes for the exact corrected executor package above. Task 3.1 is complete; root may prepare the merge-ready commit/PR and obtain one exact-source full CI run.

## Gate 5 CI-classification delta review — APPROVED, 2026-09-12

- Reviewer: independent Codex reviewer `/root/gate5_case_import_final`; authored neither the correction nor the tests
- Reviewed production commit: `4b0af3d230725a5ff9377d474b7eab7b5efd9579`
- Gate 3 delta package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T100324Z-98cbc2e222/package.json`
- Pre-review harness source: `ce485d76fbbfb6181c72afa944d5f6792ef28ab7cbb213e7b84b53a169cf1f62`

Complete findings: none. Since `4b0af3d2`, the reviewed functional delta only moves `tests/Yii2/yii2_imports_workforce_001_test.php` from policy category `unit` to `integration` and executable suite `unit` to `db`; the remaining change is the append-only independent Gate 3 approval record. Production, specification, OpenSpec verification input and executable/support test bytes are unchanged. The classification is correct because the test's unknown-`Throwable`/redaction path connects to the prepared MariaDB service before injecting the owner failure; the change preserves execution and all A1–A4 obligations rather than weakening coverage.

Independent Standards and Spec axes both APPROVED with no findings. Bounded evidence is GREEN: verification inventory `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789207811884920000-72af1eae479240cdac538b119c2121f5.json`; change-verification graph `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789207811879290000-9ee5ace89e7c498aaed22decd3c3bbd4.json`. Prior exact-production focused evidence remains valid because production/spec/test bytes did not change. No DB or local full-suite rerun was needed or performed.

**APPROVED.** Gate 5 accepts this classification-only delta. Final committed-source packaging and exact-source CI remain required; current CI/merge/deployment status is not inferred from this review.
