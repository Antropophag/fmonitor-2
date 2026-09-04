# Assignment-order original setup — V4 affected-list test gap

Date: `2026-09-05`

Status: **GATE 2 RESTART REQUIRED**.

The missing-prerequisite correction changed every normal setup axis to establish
exact V4 first. V14 requires V4→V5 publication last and therefore requires
`fm2_process_user_capabilities` as the final `APPLIED.affectedTables` member.
The dedicated capability test asserts this. The main setup test still expects
the former seven-table-only list for the same clean V4 state (and corresponding
partial suffixes).

No implementation can satisfy both public-seam expectations. Current production
corrections remain uncommitted and backed up. Task 2.2 is reopened so all V4
normal-axis expected affected lists can be aligned with the approved v14
contract, followed by fresh Gate 3.
