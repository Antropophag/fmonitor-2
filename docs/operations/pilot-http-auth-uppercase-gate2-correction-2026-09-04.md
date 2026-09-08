# PILOT-HTTP-AUTH-001 — uppercase exact identity Gate 2 correction

- Date: `2026-09-04`
- Independent integration rereview: `aa97d4f`, `CHANGES_REQUESTED`
- Production changes: none

The root `/pilot/` shell remains under `PILOT-HTTP-AUTH-001 v0.12`; local RBAC
successor approval applies only to `/pilot/objects`. The fixture deliberately
contains a distinct active exact row `SIDOROV@shlz.ru` named `Upper Exact`.
Therefore byte-exact resolution is 200, not 403.

The corrected assertion requires 200, exact uppercase-row display identity,
absence of lowercase-row substitution and the inherited security envelope.
Case folding is still forbidden: mixed-case principals without an exact row
remain 403. Fresh independent Gate 3 is required for the changed test hash.

After the corrected uppercase case passes, the unchanged downstream local-table
fault expectation remains RED (`503` expected, compatibility `200` actual) and
requires its own independent classification; this correction does not decide it.
