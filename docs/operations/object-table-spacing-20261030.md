# Object queue spacing —2026-10-30

Owner requests removal of “Следующий шаг” from object table because plan dates are cramped. Bounded reversible presentation correction: root author of implementation and existing-test expectation update; independent sol/low review before delivery. Prior owner short code→review→prod authorization persists; fullCI/ordinary lifecycle deferred, no full local suite.

Acceptance: remove only next-step heading+cells, keep comments/actions/progress/filters/pagination, display plan start/end on separate nonwrapping lines with4px gap, preserve mobile label/date alignment. No domain/query/schema/writer changes. Two runtimefiles capturedfromproduction with exact host/container hashes. Existing queue HTTP regression is focused verification; no new test for this low-impact layout change.

Worktree `fmonitor-2-object-table-spacing-20261030`, base6af4cf69. Evidence `~/.local/share/fmonitor-2/object-table-spacing-20261030/`. Status in progress.

Delivered2026-10-30 08:30Moscow. Focused existing Yii queue HTTP/read matrix PASS; PHP lint/diff-check PASS. Independent review `reviews/code/OBJECT-TABLE-SPACING-20261030.md` APPROVED exact2runtimehashes. Atomic production host/PHP deployment passed source preconditions; rollback backup `/home/antropophag/.local/share/fmonitor-2/object-table-spacing-20261030/otiz-ui-20261030T053029Z`. Public versioned CSS hash matches review; health/ready HTTP200. Live Safari table after refresh: no next-step column, start/end dates visibly separated without line breaks, comments/inspection action retained. Read-only verification, no business data changed. FullCI/ordinary gates remain owner-deferred; no overallGREEN/fullsuite claim. Root role package prepared. Owned ephemeral DB cleaned up.
