# Yii2 user access — #76

## Current candidate

Root prepared complete YII2-USER-ACCESS-001 specification, dependency matrix,
owner/HTTP, concurrency and browser tests before implementation. Separate sol/low
executor and independent reviewer follow. Current owner authorization is the
2026-09-10 autonomous #82 → #76 assignment. #82 is delivered (PR84).

Pre-Gate2 verification plan: `openspec/changes/yii2-user-access/verification-input.json`
→ `.local/verification/76-plan.json`; all e2e/governance/integration/unit obligations
read. No schema change (v24), no new dependency, no stand switch. Isolated DB on
existing test service23306, random database/user per fixture, DML-only application
connections. Composer lock installed with repository-owned bootstrap.

## RED

- `php -d display_errors=0 tests/Yii2/yii2_user_access_001_test.php`:
  intended guest admin route returns404 instead of303/login.
- `php -d display_errors=0 tests/Yii2/yii2_user_access_concurrency_001_test.php`:
  intended public YiiUserAccess application owner absent.
- `php -d display_errors=0 tests/Yii2/yii2_user_access_browser_001_test.php`:
  real browser login works, users route returns404 instead of200.

Logs: `/tmp/76-user-access-final-red.log`, `/tmp/76-concurrency-final-red.log`,
`/tmp/76-browser-final-red.log`. Tests individually run on isolated fixtures.
All are intended failures after working setup. The later browser asset assertion
is additive to the same missing-route RED, recorded before review.

Inventory registration and historical-baseline additions are included upfront;
`python3 tests/Verification/verification_inventory_001_test.py`: 15/15 GREEN.
PHP/JS syntax checks passed. No implementation or full CI yet.

## Remaining #76

Current Yii routes: auth/logout/roles/health/assets and OTIZ settlement/read/export.
Production still uses public/runtime.php → rapid router; no cutover occurred.
After this family: object queue/card/process/inspection/completion, remaining OTIZ
build/accept/reconciliation, console/jobs/imports, runtime retirement and rollout.
Read-only independent audit `/root/yii76_inventory` confirmed the broad remaining
map and object queue's hidden schema/bootstrap edge. This is not full #76 completion.

## Gate3 correction candidate

First independent Gate3 returned one complete list of five coverage groups.
Root corrected the entire matrix before resubmission: normalized directory and
rendered permissions/phone/activation flags plus no-write/no-secret assertions;
complete admission/HEAD/missing/stale-CSRF and schema failure cases; missing
command rejection and issuer/time/history cases; reissue/reissue concurrency;
raw-token log checks and deterministic session delivery fault/reissue recovery.
Existing Yii auth/session and Otiz HTTP/browser regressions are explicit focused
plan obligations after the shared Request change. Historical inventory additions
remain explicit (15/15 GREEN).

The session fault fixture was exercised on the already-working login route before
submission: actual native write marker + safe503 (`SESSION_DELIVERY_FAULT_FIXTURE_VALIDATED`).
Fresh four-test RED logs: `/tmp/76-http-rereview-red.log`,
`/tmp/76-edges-rereview-red.log`, `/tmp/76-concurrency-rereview-red.log`,
`/tmp/76-browser-rereview-red.log`; all reach intended missing owner/route with
working isolated setup. No production code exists yet and no microcommit was
created for this correction. Gate3 remains pending rereview; no approval inferred.

## Whole-matrix audit after second Gate3 return

The second return identified four unasserted directory fields and questioned the
combined-log oracle. Root re-audited the whole normative matrix before another
submission, adding user/global-role timestamps, membership IDs/names, role count,
and direct-owner committed permission revocation (in addition to HTTP revocation).
All other read, command, rejection, concurrency, rollback and browser clauses map
to the main/edges/concurrency/browser files; existing shared-CSRF regressions stay
in the required plan. No implementation starts before approval.

The log finding was investigated rather than assumed: real fixture probes on
both login200 and missing activation404 with a query token show
`COMBINED_LOG_CONTAINS_TOKEN=no`; `/tmp/76-log-boundary-probe.txt` retains the
activation result and safe logs. The test router always handles the request via
public/yii.php (never returns false), so PHP's native static/request-target logger
is not invoked; Yii safe errors share the captured stderr. The original assertion
therefore remains applicable to this actual application harness.

Separate runtime concern found during this audit: deployed nginx uses its default
combined access format, which includes query strings. Before final Yii activation
cutover, #76 must remove bearer query tokens from nginx access/error diagnostics.
This application-harness result is not proof of the outer proxy logging policy;
production cutover remains pending, with this dependency now explicit.

## Implemented candidate for Gate5

Final Gate3 APPROVED (three verdicts total; two returns, with the log finding
withdrawn after the actual router probe). `/root/implement76_access` authored all
production code. Main/edges/concurrency/browser and existing auth/session/Otiz
HTTP/browser GREEN; inventory15/15, compose policy PASS, planner12/12, runtime
storage PASS, architecture guard59/59. No local full run or CI yet.

Corrected focused failures before freeze: method routing404→405 via VerbFilter;
array activation input503→400 before infrastructure catch; missing users.js asset
map; browser filtering display state; missing explicit submit button types.
Root also required the correct Yii Transaction isolation constant and preservation
of database-derived Moscow DATE_ATOM in existing VARCHAR timestamp fields.

Root rejected the first functionally green presentation before Gate5 because it
lost corporate geometry. The whole UI was restored against current roles/user
views and public shlz styles. Bounded desktop/mobile review then caught omitted
navigation/logout icons; actual icon markup and aria labels restored, preserving
incumbent fixed mobile nav and workspace padding. Last-row controls remain
reachable, table scroll is local, no page overflow, logout works at both sizes.
No domain/test changes were made for these presentation corrections.

Latest logs: `/tmp/76-user-access-green-final.log`,
`/tmp/76-user-access-edges-green-final.log`,
`/tmp/76-user-access-browser-icons-green.log`.
Visual evidence: `/tmp/76-access-icons-final/{users-desktop-1440x1000.png,users-mobile-390x844.png,report.json}`;
activation: `/tmp/76-access-visual/activation-{desktop-1440x1000,mobile-390x844}.png`.
Impeccable context loaded once; incumbent world preserved, no DESIGN.md invented;
bounded final defect scan returned no findings. Mobile fixed-bar screenshot position
is expected in full-page captures; missing icons were the actual defect.

Frozen core hashes: owner `3b63efa597935d6df07c0751cc3c24162ceb4eb4c9dcc91d511b7531eb656d31`,
controller `a3a2201d5526f3f37e061704e5b03c667cb0ff36fce9b7b19b56a23f9cf48aaa`,
users view `f0cf16f41ab2642f50d0e2741896c42e4994e033f26a879618dca318f694ce10`.
Essential asset paths are now explicit in planned_paths; actual-path binding already
covered them. Separate proxy-privacy slice runs in an isolated worktree and will
be integrated before the combined authoritative CI.

## Actual architecture correction before Gate5

Root ran actual `make architecture-check`, not just the checker's unit suite.
It initially rejected SQL ownership, a166-line hotspot, and view/JS select false
positives. Full inventory: `/tmp/76-access-architecture-check.log`. A first149-line
renaming-only adapter was rejected by root as an inadequate design correction.
The final facade now composes six coherent internal MariaDB collaborators on one
connection/transaction context; no inheritance/monolith or baseline change remains.

Final actual checker: `/tmp/76-access-architecture-composed.log` — HTTP qualification
PASS and ARCHITECTURE CHECK PASSED (7 rules). Post-refactor main, edges, concurrency,
browser logs `/tmp/76-access-{main,edges,concurrency,browser}-composed.log` are GREEN.
Previous auth/session/Otiz neighbor results remain valid; the later refactor changes
only this module's internals/identical dropdown rendering. Final interface hash
`4d91392ae310d16df8d20b77479fe815fc6c654f5a87eb097e3e4532b9c3dd2c`.
No code/test commit was made for individual corrections; Gate5 now receives the
complete corrected candidate and full history of local failures.

## Gate5 authorization precedence finding

First actual Gate5 returned CHANGES_REQUESTED: invite/role/status had early field
validation (and status self-target check) before transaction/authorization. Root
added a direct-owner regression for unauthorized malformed commands and missing
authorization schema on malformed/self commands. The test observes public results,
throws and unchanged persisted facts, without private-method assertions. The delta
returns to independent Gate3 before code correction. Earlier users-view f0cf hash
is historical visual checkpoint; final dropdown-rendering hash is
`9147ccc6ed7611f1283894305bc0619c95e11503e64351c61aea354b5d9143ca`.

## Authorization precedence correction

Changed test delta independently APPROVED; implementation moved only invite field,
role action and status action/self validation after exact authority inside the
shared transaction. Final main/edges GREEN:
`/tmp/76-access-main-auth-precedence-green.log`,
`/tmp/76-access-edges-auth-precedence-green.log`.
Actual architecture PASS7 + qualification:
`/tmp/76-access-architecture-auth-precedence-green.log`.
Unchanged browser/concurrency/neighbors retain their earlier green evidence.
Gate5 delta rereview pending; no code commit or CI yet.

## Reviewed implementation checkpoint

Gate5 authorization delta APPROVED. The reviewed source snapshot is
`/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-access-gate5-auth-corrected`,
patch `9e91867a466c25217c88c649af01143026e02474057bf6a75494fdb43ae1659e`, base2cd9ffa0.
Latest code review: `reviews/code/YII2-USER-ACCESS-001.md` (prior findings preserved).
Six verdicts total: four Gate3 verdicts (two returns, two approvals) and two Gate5
verdicts (one return, one approval). Root preparation also caught/fixed visual and
actual architecture problems before Gate5; they are reported above rather than
counted as independent verdicts. Tokens/cost not measured. Code and all requested
focused gates are complete; combined proxy privacy integration/full CI/merge remain.
