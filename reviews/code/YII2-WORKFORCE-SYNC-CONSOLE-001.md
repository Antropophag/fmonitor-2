# Independent Gate 5 code review — YII2-WORKFORCE-SYNC-CONSOLE-001

- Reviewer: separately tasked agent `gate5_workforce_sync`; authored none of the normative specification, OpenSpec, tests, Gate 4 implementation, or verification evidence.
- Review date: 2026-09-12.
- Exact prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T140138Z-d64bd49d31/package.json`.
- Exact reviewed source: reconstructible dirty snapshot over base `a8ba73a926d1031c8b039555d0b6c3f142abd991`, source digest `3a5833615c497f62d15518e4fcc86dc518ef16a60a5861246dc89dd5f8caf0c0`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T140138Z-d64bd49d31/snapshot/source.patch`, SHA-256 `235d6e9e79a53874bdbc204b3b000ed9e3be14de40c7e7fdda420a16dfaad49a` (matches `snapshot/manifest.json`).
- Reviewed production delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T140138Z-d64bd49d31/delta.patch`, SHA-256 `a60fcfaee5550ed39c4dda4d400f63c5692558f30bcc9a44475d1a30ae34fcf7`.
- Normative contract: `specs/YII2-WORKFORCE-SYNC-CONSOLE-001.md`; lifecycle binding: `openspec/changes/yii2-workforce-sync-console/verification-input.json`.
- Final Gate 3 approval: `reviews/tests/YII2-WORKFORCE-SYNC-CONSOLE-001.md`, final narrow rereview source `3f9b791dce10b7d6b4fa5aa07dfffc6a46fc7d4f30148c4b9031c94fe721013c`, verdict `APPROVED`.

## Finding

1. **HIGH — a successfully allocated mysqli resource is not guaranteed to be closed when connection setup fails after construction.** Location: `app/YiiRuntime/WorkforceSyncConsole.php`, `openDatabase()`. The method constructs `new mysqli(...)`, then calls `$database->set_charset('utf8mb4')` before returning the handle. If `set_charset()` throws, the handle never reaches either `run()` or `runJob()`, so neither caller's `finally` can close it; `composeFromEnvironment()` only removes the staged token and rethrows. This violates A2 and OpenSpec design decision 1, which require the shared composition to own one mysqli lifecycle and guarantee terminal release, and it affects both the operator command and scheduled-job composition. The existing ownership test only searches the source for `finally` and `->close()` and therefore remains GREEN for this leak. **Correction:** make `openDatabase()` own partial initialization (for example, initialize/connect/set charset inside `try` and close the handle in `catch` before rethrowing), while preserving the command's `DATABASE_UNAVAILABLE` mapping and jobs retry behavior. Add a deterministic lifecycle witness for the post-allocation setup-failure path; because this is a newly identified test sensitivity gap, the changed test must return through Gate 2/3 before Gate 5 rereview.

## Conformance and evidence assessment

Apart from the finding above, the reviewed production delta conforms to the bounded contract: the controller enforces the exact raw argv before composition; outputs are closed JSON with empty stderr; file/direct Bitrix modes retain priority and redaction; the canonical synchronization owner remains the sole SQL/history owner; UUIDv4 is used for manual runs while jobs retain immutable `jobIdentity`; the alias delegates to the exact Yii route; and jobs retain their existing handler outcome mapping, queue/lease/retry/deduplication ownership. Package/load checks include the new controller, shared composition, canonical owner, route and launcher while excluding `rapid-pilot`, demo, web/session and `app/Otiz`. No production OTIZ path, schema, schedule, stand, web route, or deployment change occurs in the reviewed delta.

The package declares ten focused records, all `GREEN` and all bound to exact source `3a5833615c497f62d15518e4fcc86dc518ef16a60a5861246dc89dd5f8caf0c0`: workforce transport, real DB success/repeat, ownership, package closure, jobs workforce, jobs linked retry, retained Bitrix delivery, retained workforce synchronization/history, architecture guard, and verification-CI inventory. The records report exit code 0 without source drift. During review, `git diff --check` and `openspec validate yii2-workforce-sync-console --strict` were also GREEN. These results do not cover the partial-initialization leak above.

Harness state reports PR/CI and deployment as `UNKNOWN`. They are not treated as approval, GREEN CI, merge, or deployment authorization.

## Verdict

`CHANGES_REQUESTED`

Gate 5 is blocked for exact source `3a5833615c497f62d15518e4fcc86dc518ef16a60a5861246dc89dd5f8caf0c0` until the partial mysqli initialization path is closed, its regression witness is independently approved, fresh same-source focused evidence is GREEN, and the corrected complete production delta is independently rereviewed. OTIZ remains excluded.

---

## Gate 5 correction rereview — 2026-09-12

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T141138Z-c80e1ccf16/package.json`.
- Corrected exact source: reconstructible dirty snapshot over base `a8ba73a926d1031c8b039555d0b6c3f142abd991`, source digest `62e74430c30add595027e6d7488fa9883dc150fe401758e027708644d29a5530`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T141138Z-c80e1ccf16/snapshot/source.patch`, SHA-256 `68dbc0117172738a7478779507e7458705f208e15bc77e0d3ea0be7241ed0b3e` (matches `snapshot/manifest.json`).
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T141138Z-c80e1ccf16/delta.patch`, SHA-256 `58c9757f804448d969d8c5414ac4bc8c15ad5127033be3347c8c82a0cf93a4f0`.
- Gate 3 correction approval: `reviews/tests/YII2-WORKFORCE-SYNC-CONSOLE-001.md`, “Gate 3 delta review — partial-initialization resource release”, exact source `d0283b430cb113088758554efac15af72d4088da859fb707e367d10d94badce0`, verdict `APPROVED`.
- Reviewer independence is unchanged; this reviewer authored neither the correction test nor the implementation.

### Prior-finding resolution

The sole Gate 5 finding is resolved. `WorkforceSyncConsole::openDatabase()` now guards charset initialization after successful `new mysqli(...)` allocation, closes the owned handle in the catch path, suppresses only a secondary close failure, and rethrows the original error. The normal success handle still returns to the existing caller `finally` blocks. Thus partial initialization, ordinary command completion/failure, and jobs completion/failure all retain an explicit owner and terminal release. The correction does not change configuration parsing, result/redaction shapes, canonical synchronization ownership, manual UUID/job identity, retry classification, queue/lease/deduplication behavior, package composition, or public routes.

The independently approved ordered source witness is GREEN and detects the original allocation/`set_charset`/missing-close regression. The correction package contains ten records, all `GREEN`, all bound to corrected exact source `62e74430c30add595027e6d7488fa9883dc150fe401758e027708644d29a5530`, and no missing tests: workforce transport, DB success/repeat, ownership/resource release, package closure, jobs workforce and linked retry, retained Bitrix delivery and workforce history/synchronization, architecture guard, and verification-CI inventory. `git diff --check` and `openspec validate yii2-workforce-sync-console --strict` are also GREEN.

No new findings. The complete corrected production delta satisfies A1–A4, keeps one application/history owner and one shared composition boundary, preserves jobs compatibility and closed outputs, and contains no OTIZ, `rapid-pilot`, web/stand, schema, schedule, or deployment expansion.

Harness PR/CI and deployment state remains `UNKNOWN`; it is not treated as GREEN, merge, or deployment authorization.

### Rereview verdict

`APPROVED`

Gate 5 passes for exact source `62e74430c30add595027e6d7488fa9883dc150fe401758e027708644d29a5530`. Publication still requires root confirmation of reviewed bytes and subsequent exact-source PR/CI handling; deployment remains separately unauthorized.
