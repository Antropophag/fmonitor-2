# Assignment-order original setup — CHECK parentheses normalization gap

Date: `2026-09-05`

Status: **GATE 2 RESTART REQUIRED**.

The Gate-3-v7-approved verifier passed nullable-default observation and reached
the revision lineage CHECK. MariaDB returns the same boolean expression after
removing redundant whole-expression and branch parentheses, while the literal
oracle retains `((left) OR (right))`. The current normalizer removes only
parentheses wrapping the whole returned string and cannot canonicalize these
two equivalent forms.

All other revision CHECKs observed before the mismatch were exact. The
unreviewed implementation is preserved outside the repository and removed from
the worktree; task 3.1 remains incomplete. Task 2.2 is reopened. RED author
must add a narrow boolean-expression canonicalization/preflight that preserves
operator precedence and remains sensitive to changed operands/operators, then
obtain fresh Gate 3.
