# Gate 3 — YII2-OTIZ-WORKFLOW-001

Status: `APPROVED`.

## Prepared source

- Base: `origin/main` at `adf30398c62ca4d9fc77b1d3ecdaed68a5a228bc`.
- Harness package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T092815Z-9715bf4ff4/package.json`.
- Verification plan SHA-256: `a75277710ed3d5abb235426f083593ecd9fb82a9c76165a66fb909e84ef30d90`.
- Root authored scope, normative spec and tests. No production implementation exists in the candidate.

## Intended RED evidence

1. Первоначальный substring scan заменён runtime include probe. Реальный request через `public/runtime.php` при checkout-local Yii vendor не загружает `rapid-pilot/Otiz.php`; expectation исправлен на `GREEN`.
2. `php tests/Yii2/yii2_otiz_workflow_001_test.php` — `SETUP_FAILURE`: preflight `GET /pilot/login` returned 500, therefore this is not accepted as intended RED.

## Unresolved focused failures

- Existing mapped `php tests/Yii2/yii2_otiz_settlement_001_test.php` also returned 500 instead of its baseline guest redirect; this confirms a local Yii2 fixture/runtime problem precedes the new route assertion.
- Direct `node tests/Otiz/snapshot_publication_browser_001_test.mjs` is not a standalone command and failed because its launcher-provided config argument was absent. Verification input/plan must map its real public launcher instead of treating this helper as executable.
- Диагноз setup failure: новый worktree не содержит `vendor/autoload.php`; `public/yii.php:7` падает до routing. Соседний documentary worktree содержит собственный `vendor/`, поэтому ошибка относится к подготовке нового checkout, а не к imports/workforce.
- Диагноз browser mapping: `snapshot_publication_browser_001_test.mjs` является helper с обязательными positional arguments. Реальные launchers — `tests/Support/OtizBrowserFixture.php` и `tests/Runtime/production_runtime_browser_001_test.php`; direct mapping в verification input неверен.
- Gate 2 и Gate 3 остаются blocked до установки locked Composer dependencies в этом worktree, замены browser mapping на публичный launcher и повторного получения intended RED после успешного setup.

Existing application/publication and Yii2 settlement tests remain mapped as expected GREEN; the independent reviewer must run/inspect the complete prepared plan and decide traceability, seam sensitivity and matrix completeness. `UNKNOWN` or silence is not approval.

## Independent verdict

`CHANGES_REQUESTED` on package `20260912T094014Z-6a62424ba7`.

The two REDs are genuine, but the complete Gate 3 matrix is not yet sufficient:

1. Add an authorized public calculate → inspect → accept → export → payment → reverse/reconciliation journey.
2. Add public rejection/no-new-fact cases for input, CSRF, permission, operation conflict, blockers/incomplete/immutable/not-found, export and decision failures.
3. Exercise replay/concurrency through Yii2 HTTP, including actor/operation forwarding and duplicate-fact prevention.
4. Cover every route/asset without fail-fast hiding later gaps.
5. Replace the substring dependency check with runtime evidence resistant to alternate includes/factories/aliases.
6. Extend real browser coverage beyond a seeded accepted snapshot to calculate/accept/reconciliation, navigation and critical rejection states.

Production implementation is not authorized until a fresh complete candidate receives independent `APPROVED`.

## Gate 2 correction candidate

Root added the complete public matrix requested above:

- `yii2_otiz_publication_browser_001_test.php`: Yii2-only login and full nonzero calculate/response-loss replay/inspect/accept/XLSX, exact snapshot/receipt/event/actor/totals and workbook assertions.
- `yii2_otiz_commands_001_test.php`: CSRF, near-match permission, invalid date, operation conflict, NOT_FOUND/IMMUTABLE/BLOCKERS/SNAPSHOT_INCOMPLETE mappings and unchanged aggregate fact fingerprint.
- `yii2_otiz_decisions_001_test.php`: authenticated reconciliation/quarantine filtered reads, accepted decision, replay, conflict/stale rejection and exactly-one append-only row per ledger; active-baseline/historical empty states.
- `yii2_otiz_workflow_001_test.php`: complete non-fail-fast route/asset admission inventory.
- `yii2_otiz_runtime_boundary_001_test.php`: real included-files evidence for every migrated GET/POST path; current outcome GREEN through Yii runtime.

Fresh evidence/package and the second independent verdict supersede the first verdict below; until then status remains `CHANGES_REQUESTED`.

## Final independent Gate 3

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T132930Z-21667748b4/package.json`
- Source: `61189af91f9958358fe0b6564cdf932626bce0e4e614a43a34953fbd926a7d00`
- Plan SHA-256: `3d64201a9a088cda285227376d08b60b1b181fdfc987b4ac1e3cc32ee3bb9080`
- Verdict: `APPROVED`; no blocking findings. Gate 4 may proceed without changing approved expectations.

## Final test-only delta review after Gate 4 fixture corrections

- Reconstructed source on current `origin/main`: `f87aedf34f371c9a3a1efa1176a8e1ceb1ca5cac4c13c1ebe984447a2a53839a`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T140947Z-b43bef5b48/package.json`.
- Verdict: `APPROVED`; no blocking findings. Production candidate was explicitly outside this verdict.

## Expanded Gate 5 correction matrix

- Genuine test-only source: `9b56014276e3526d81ff95e88543b0f037f88906cb6f09938c3fd3f604a82b3e` on current main `a8ba73a926d1031c8b039555d0b6c3f142abd991`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T145237Z-94265b4777/package.json`.
- Evidence: 12/12 expected outcomes, including direct fallback dependency RED, public runtime GREEN, HTTP concurrency/rollback, safe guest return, rendered decision UI and generated-snapshot settlement/reversal.
- Verdict: `APPROVED`; no blocking findings. Standalone `otiz-oracle-router.php` requires no production hooks.

## Final populated-read and fallback regression Gate 3

- Test-only source: `c1e6e2e26e246fb02c9fb60a38ad37ab10a6a3f8dcd78e75d01048671d0b7c31` on main `a8ba73a926d1031c8b039555d0b6c3f142abd991`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T152311Z-7ad7e6490a/package.json`.
- Evidence: 13/13 expected outcomes, including populated page-two active/historical reads, authenticated object/calendar fallback and the test-owned location-independent oracle.
- Verdict: `APPROVED`; no blocking findings. Production implementation explicitly excluded.

## PR #103 CI correction Gate 3 — 2026-09-12

- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T172112Z-7919ca80ea/package.json`.
- Reconstructible snapshot: base `885a55f1ec1e36dca3ba4e52cd0013e5e5b32608`, patch SHA-256 `3af02fba6f495974148f5b11c7130186563c129c18dac74561d178c1a5f84347`; source `f0c173a8fdd93a7ec910a290ae69fe5d5f8b4892428e3398a2f8661e68c9da5d`, executable source `26c39028e9f1ce0853f1f8214335d19bcf0a24c8c956c40f00e9e4341d7ed623`.
- Original CI `34706549891` is retained RED evidence for exactly five regression failures: jobs Compose keys, production Compose keys, real OTIZ DDL inventory, current v24 recovery prepare, and historical v22/v23 forward prepare. No fabricated current RED is claimed; this correction closes existing regression expectations and therefore maps the five tests to GREEN.
- The reviewed tests retain exact outcomes and strengthen coverage of real `app/Otiz` owners, required Yii keys, nonempty Yii session bytes/modes, historical rows/AUTO_INCREMENT, explicit current-image prepare and replay no-op. The bounded restart poll still requires HTTP 200 and does not prepare or mutate state.
- Evidence: all five mapped records in the reviewer package are `GREEN` with no source drift; preflight `1789233570809498000-c56c3427931a400aa27b493ce4caa03a` is `GREEN`.
- Verdict: `APPROVED`; no findings.

## PR #103 governance fixture delta Gate 3 — 2026-09-12

- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T173451Z-9d696bb067/package.json`; snapshot base `a5eb2db877503ee5dc713c5e0796b1212142e7a7`, patch SHA-256 `d7f9785354de45888c57bcce03e40f935b0e775ca68dd9dd862345ab55beb117`, source `5e7cd8f62f970bd45f6e71c8b61fb3ef72c5575a0761138b8c383e9851f9289d`, executable source `abf09fbb77affff2a5c04f38c5223e5213e72541d4ef7f5b88da3a3eb981d847`.
- CI `34708129873` retained one governance suite regression containing two fixture failures; plan, fast, unit, e2e and both integration shards succeeded. Aggregate verify failure remains historical RED evidence. Full governance log: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/stdout/1789234614010953000-2518fdb9d6794cc287fb3bebc2d00691.log`; its retrieval runner succeeded, not the failed test.
- The delta makes source identity deterministic under Python 3.12 by copying repository `.gitignore`, tests positive preflight against its own registered synthetic unit/base, and verifies canonical retained-record containment and payload. Existing mutation, mismatch, missing-dependency and unrelated-evidence rejections remain unchanged. Two intermediate correction failures remain retained and are not approvals.
- Exact Python `3.12.11` evidence `1789234429494845000-e1e4ab3148764258a7e0ffc2fc20661e` is `GREEN`: 15/15 methods, no source drift.
- Verdict: `APPROVED`; no findings.
