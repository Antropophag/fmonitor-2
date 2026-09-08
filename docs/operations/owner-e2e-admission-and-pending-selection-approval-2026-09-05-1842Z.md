# Owner decisions — E2E admission and pending composition correction

Recorded 2026-09-05 18:42 UTC. User was presented exactly two pending decisions:

1. E2E amendment revision3: replace stale table expectations with approved list
   representation, retaining RED→GREEN, independent reviews and all remaining
   protected checks.
2. Permit FKR to correct saved composition before original acceptance by making
   a new immutable version with visible prior history; accepted original
   composition is not changed by this action.

The user replied exactly: **«утверждаю разрешаю»**. In the immediate context this
approves item1 and permits item2. Do not re-ask either decision.

## Exact E2E Gate1 owner approval

APPROVED:
`docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md`,
revision3, SHA256
`c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e`.
Independent technical readiness:
`protected-e2e-admission-amendment-technical-review-v3-2026-09-05.md`.
The unchanged candidate's pending-owner prose is superseded by this record.

This authorizes the exact candidate's next gates, not immediate ungated edits.
First the independent admission oracle must receive intended RED/Gate3/GREEN/
Gate5. The exact protected assertion patch then follows its separately specified
reviewed-patch gates. Full E2E remains unskipped; downstream failures are not
accepted as VERIFY_OK. No manual-registration target, PDF expectation change,
production renderer change, CI/publication or gate waiver was approved.

## Pending-selection policy

APPROVED product policy: REPLACE_PENDING may create a new immutable selection
identity/version before original acceptance, retain old selection/template
history visibly, and never change accepted original composition through that
operation. This is approval of the specific user-facing policy, not blanket
technical Gate1 approval for the consolidated selection specification.

Current selection v0.4 hash for reference:
`91e41ced07c881dfd67596ccafa2ac0246af81200945df5100e7190131d9a00f`.
Its owner-pending replacement-policy statements are superseded only as policy;
DTO/schema/ports/compatibility and full technical Gate1 remain unfinished.
The newly assigned v0.4 independent readiness review was interrupted for the
user-requested session restart and has NO result. Do not infer approval.

The user also requested a session restart because context is full. No new RED,
implementation or long-running check was started after these approvals; preserve
this checkpoint and resume in the new session.
