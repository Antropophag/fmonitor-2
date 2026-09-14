# Issue #130 — checklist HTTP authorization

Owner authorization 2026-09-14: implement GitHub #130 in a separate worktree
from freshly fetched origin/main; deliver separate PR, no merge or deployment.
Worktree `/Users/antropophag/code/fmonitor-2-issue-130`, branch
`codex/issue-130-checklist-projection`, base
`44880bbe4df579789a094fda526e6a4415598b29`.
The imports/workforce WIP in the original checkout remains untouched.

Contract: [CHECKLIST-HTTP-AUTHORIZATION-001](../../specs/CHECKLIST-HTTP-AUTHORIZATION-001.md).
Binding: [issue-130-verification-input.json](issue-130-verification-input.json).
Root authors specification and tests; independent `review130` sol/low reviews;
`execute130` sol/low authors the implementation after Gate 3. Autonomous spec/test
delegation was not authorized. Makefile, legacy-import, integration config and
OpenSpec yii2-imports-workforce remain outside scope.

## Gate 2

Extended existing `tests/Yii2/yii2_inspection_journey_001_test.php`, already in
the full verification inventory. No new shared inventory registration.
Real isolated HTTP request reaches owner authorization using valid credentials,
CSRF and JSON: expected 403, actual 422. Successful setup and preceding authorized
accept/duplicate/conflict/business checks exclude transport/DB setup false RED.
Private harness evidence:
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789401513224323000-89db8af30b054a18b6b112c2fabcf21e.json`.
Initial direct RED repeated after completion of the test candidate, then through
harness to obtain source-bound evidence required by prepared Gate 3 packages.

Gate 3 APPROVED: [record](../../reviews/tests/CHECKLIST-HTTP-AUTHORIZATION-001.md).
Gate 4/5 evidence and final delivery state are recorded in the append-only
[code review](../../reviews/code/CHECKLIST-HTTP-AUTHORIZATION-001.md); live PR/CI
state is resolved with `python3 tools/delivery/harness.py state` and GitHub.
Only bounded focused local checks are authorized; full matrix runs in GitHub CI.
No local full make test or make verify, merge, deployment, stand or role-policy
changes. CI failures, if any, require complete failed-job/REGRESSION_FAILURE
inventory before correction; UNKNOWN never means approved or GREEN.
