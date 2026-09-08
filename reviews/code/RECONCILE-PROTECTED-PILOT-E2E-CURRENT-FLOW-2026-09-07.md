# Protected pilot E2E current-flow — independent code review

Reviewer: `/root/photo_review`; artifact author: `/root/e2e`.
Verdict: **APPROVED** for test/fixture/bootstrap changes only.

## Standards

No finding. The package changes no runtime behavior. Synthetic state is created
through existing fixtures and canonical migrations; user operations run through real
HTTP/application seams. Browser automation is headless Chrome. Child processes use
owned process groups with bounded TERM/KILL cleanup and preserved failure diagnostics.
The bootstrap timeout extension is parameterized and applied only to the protected
journey (300 seconds); existing children retain 25 seconds.

## Spec

No finding. The protected flow exercises queue, selection, optional inline template,
initial/corrected original, distinct opener `open_confirmed`, 41 checklist items,
seven photos, 85% work progress, PTO/declaration and durable 100%. It asserts exact
v19 schema precondition, distinct roles, absence of standalone apply authority,
download integrity/read-only behavior, append-only counts, exact opener/date, legacy
and workforce preservation, foreign sentinel preservation, browser/network errors and
fresh MariaDB reads. Retained negative contracts remain mandatory children; failures
are not converted to skips.

Reviewed hashes and execution evidence are pinned in
`reviews/tests/RECONCILE-PROTECTED-PILOT-E2E-CURRENT-FLOW-2026-09-07.md`.
PHP/Node syntax and focused diff-check PASS. The bounded protected child is GREEN;
the enclosing bootstrap's later unrelated CSS timing failure and full `make verify`
remain unresolved evidence outside this approval.
