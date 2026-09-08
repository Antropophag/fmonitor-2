# Selection command v0.3 — closed API candidate

Date: 2026-09-05. Author: `/root`.
Base: `71eea1b3f6e11f5352808051dd314b6016df0d9a`.

Task 1.1 is recorded complete using the independent seam inventory and subsequent
production/authorization/projection inspections. Task 1.2 remains under work:
the candidate now defines constructible command/mode/result APIs, status/reason
matrix, exact request comparison and authorization/replay/eligibility/CAS order.

NEW_ORDER versus REPLACE_PENDING explicitly distinguishes a new prospective
selection after an accepted order from correction of an unsigned selection.
Both preserve all previous identities and effective projections. A replacement
cannot change accepted original composition. No production capability or
persistence change is authorized by this draft.

The compact composition JSON worked example was hashed using PHP's hash
function with literal independently specified input, not production output:
`5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a`.
Normative PHP declaration block parses using TOKEN_PARSE. Diff-check passed.

Exact draft SHA-256:
`2b30da75a41dc894d43d85a53e8f06979775787f447eaf1b3f82b9675fbe84b9`.

Remaining Gate 1 integration: actual schema/atomic persistence, public
dependency/result/audit observations, physical date compatibility, optional
render handoff and effective projection owner. No RED/production begun.
