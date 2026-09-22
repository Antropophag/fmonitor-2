# Gate 5 final review: YII2-CONSTRUCTION-CONTROL-SERVER-FILTERING-001

- Reviewer: independent Gate 5 agent `/root/gate3_review`; authored none of the specification, tests, browser helpers, production implementation, or verification evidence.
- Review date: 2026-09-22.
- Reviewed exact commit: `d295c24a4489ed70607449aa62b7c32b005f8c13`.
- Reviewed diff: `3c242f34e8f30986f1b8354c4ef947a4c63936dc...d295c24a4489ed70607449aa62b7c32b005f8c13`.
- Worktree state at review start: clean.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T012144Z-667a99616e/package.json`.
- Verification plan SHA-256: `ec9fd4baf0c45ee24dd4e7779bdc98879ca2f36f6bb8eca180e7ed620a5e01a6`.
- Package candidate source: `762950a24e5a2623095b95b5cf5b319f71377dde44c35fde5a49aa569836b990`; executable source: `b4e4179ff7ab72ccc738ba346a20eb506ef7eb9dd1d41765bd79bb40a029a5bd`.
- Normative specification: `specs/YII2-CONSTRUCTION-CONTROL-SERVER-FILTERING-001.md`.
- OpenSpec change: `openspec/changes/fix-construction-control-server-filtering/`.
- Required reviews: `gate3`, `final`; latest test approval: `reviews/tests/YII2-CONSTRUCTION-CONTROL-SERVER-FILTERING-001-v8.md`.
- Verdict: `APPROVED`.

## Findings

No findings.

Two independent review axes also returned no findings: one checked specification conformance and scope; the other checked repository standards, security, SQL/JavaScript maintainability, and test integration.

## Specification and seam conformance

The implementation crosses the declared authorized GET/HEAD `/pilot/construction-control` seam and keeps the change read-only. `ChecklistController::actionQueue()` accepts only scalar strings, enforces exact ownership/completed enums, positive canonical page input, and the 160-Unicode-character query bound before invoking the read owner. Invalid inputs return controlled 404 responses. The existing exact `construction_control.read` check remains inside the owner, and authorization failures remain 403; `ownership=all` changes only the ownership predicate.

Defaults are correct: `mine`, empty query, `completed=0`, page 1. The view reflects those values in escaped controls, pagination receives the same filter map, and the JavaScript submits the GET form after search, ownership, completion, or clear changes. Empty search is disabled before submit, so clear produces the canonical mine URL without stale query/completion/page parameters. The real browser journey proves each control causes server navigation and exact new rows rather than post-filtering the current DOM.

## SQL predicate, ownership, and pagination

`MariaDbYiiChecklistRead::queue()` constructs one predicate and one parameter list, then reuses them for both COUNT and the paginated row query. Access/case eligibility, canonical completion, current-native ownership, and literal query search therefore all occur before LIMIT/OFFSET. Stable ordering remains completion, activity presence/time, then object ID; the tests independently assert exact first/second-page and tied mine ordering.

The mine predicate uses only the latest `fm2_control_engineer_assignments` sequence for the case and actor. It does not consult legacy `responsstroicontrol`, historical assignment ownership, order authorship, or the display fallback. Row decoration still runs the authoritative current-assignment coherence reader and fails closed on unavailable/corrupt assignment state. Tests distinguish positive current assignment, foreign current assignment, legacy-only ownership, and historical-then-reassigned ownership.

Completion remains canonical `pto_act AND declaration`. Default excludes completed; `completed=1` includes active and completed; the active eligibility expression continues to exclude PTO-only. Empty totals produce pages=1 and a visible server empty state. Out-of-range pages fail in the existing controlled 503 envelope without substituting another page.

## Effective details and query safety

Address, entrance, and registration projection now use `MariaDbEffectiveObjectDetails::sqlValue()`, and address/registration search uses those identical expressions. `MariaDbYiiObjectQueue` also adopts the helper, preventing divergence between list projection and construction-control search after the effective-details change.

The helper allowlists field names and validates SQL identifier/alias shapes before interpolation. User query content is always bound as parameters. Backslash, percent, and underscore are escaped before `LIKE ... ESCAPE '\\'`, and case-insensitive substring search uses lowered query and SQL values. View values and reflected query content are HTML-encoded. No user-controlled SQL identifier or HTML injection path was introduced.

## Browser, offline, and adjacent integration

The view renders only server-selected rows and the server total. `control-queue.js` no longer hides rows or rewrites count/summary/empty state. It retains IndexedDB stores, local-operation state painting, retry/sync ordering, photo payload handling, service-worker checklist prefetch, scroll restoration, shipment disclosure behavior, and row/checklist navigation.

The approved browser helper behaviorally observes queued IndexedDB state and service-worker prefetch, shipment/checklist affordances, stable rows/total, and actual filter submissions. The full inspection browser journey proves default mine, all-active, completed, query, empty, and clear navigation while retaining its pre-existing offline durability, response-loss replay, mixed operation/photo/section ordering, account isolation, CSP, and asset checks.

Adjacent active-queue, preopening, shipment, and inspection tests explicitly request `ownership=all&completed=1` only where their independent oracle requires the previous broad row universe. Their original ordering, readiness/coherence, shipment, authorization, mutation-free read, and failure assertions remain intact. The verification input and plan bind all of these changed tests and executed browser helpers.

## History, persistence, and scope

GET/HEAD paths introduce no writer, schema, migration, assignment, history, or local-operation mutation. Before/after inventories cover facts, native assignments, and effective-detail edits. Existing focused controls cover schema and photo-history characterizations. The production diff remains within the declared Yii queue/read/view/asset/effective-details boundaries; offline protocol, checklist writers, photo storage, completion workflow, and deployment are unchanged.

No speculative compatibility path or rapid-pilot domain logic was added. The implementation reuses existing public owners rather than duplicating effective-detail or assignment semantics.

## Verification evidence

Reported focused evidence for the reviewed code/test source family:

- seven-test browser profile: PASS, source `d2c0994…`;
- full inspection browser journey: PASS, source `b4e4179ff7ab72ccc738ba346a20eb506ef7eb9dd1d41765bd79bb40a029a5bd`;
- inspection schema plus four photo characterization controls: PASS in the same source family;
- architecture guard: 59 tests PASS;
- change-verification suite: 18 tests PASS;
- `pilot_jobs_compose`: PASS;
- PHP lint, Node syntax check, and `git diff --check`: PASS.

The prepared plan binds the final committed production, spec, review, PHP-test, and browser-helper bytes and reports no missing tests or unresolved acceptance mapping. Later commits in this source family are test/review records; no unreviewed production delta is present at `d295c24a`.

Formal reviewer preparation did not ingest these results because the evidence records were not harness-owned. Therefore automated review/evidence enforcement remains `UNKNOWN`, not GREEN. The package itself has an empty evidence array and `approval=NOT_REVIEWED`; this written independent review is the Gate 5 decision, not a claim that the unavailable adapter approved it.

The repository-required exact-source GitHub CI run has not been reported here. CI, merge, and deployment remain `UNKNOWN`; this approval does not authorize or imply any of them. A failed, cancelled, mismatched, or incomplete mandatory CI result must still fail closed.

## Verdict

`APPROVED`

Gate 5 passes for exact committed candidate `d295c24a4489ed70607449aa62b7c32b005f8c13`. The candidate may proceed to the selected exact-source CI consumer. Any later production, specification, test, browser-helper, verification-input, or source-binding change requires applicable independent delta review.
