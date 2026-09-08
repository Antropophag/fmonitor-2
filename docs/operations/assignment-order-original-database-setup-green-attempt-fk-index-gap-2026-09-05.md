# Assignment-order original setup — MariaDB FK support-index gap

Date: `2026-09-05`

Status: **GATE 2 RESTART REQUIRED**.

The Gate-3-v8-approved verifier reached the requests table. Its manifest expects
only the business secondary index plus the primary key, while the approved
schema also requires nullable FKs on `root_original_id` and
`current_revision_id`. MariaDB necessarily creates one supporting index for
each FK because neither column leads an existing index. `information_schema`
therefore reports two additional exact indexes.

Removing those indexes would remove or invalidate approved FK enforcement; the
implementation cannot satisfy both current oracles. The production attempt is
preserved outside the repository and removed from the worktree. Task 2.2 is
reopened. RED author must add the two deterministic FK support indexes to the
exact manifest and prove missing/extra/wrong-column sensitivity, then obtain a
fresh Gate 3.
