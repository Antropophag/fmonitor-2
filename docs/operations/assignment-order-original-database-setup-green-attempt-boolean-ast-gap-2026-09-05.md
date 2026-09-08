# Assignment-order original setup — complete boolean CHECK normalization gap

Date: `2026-09-05`

Status: **GATE 2 RESTART REQUIRED**.

After Gate 3 v9, the verifier reached the requests table. MariaDB removes
redundant grouping parentheses from all three status-dependent implication
CHECKs, just as it did for revision lineage. The approved oracle retains those
parentheses, while its normalizer special-cases only the revision expression.

The returned operands and operators were otherwise byte-equivalent. Production
is preserved outside the repository and removed from the worktree; task 2.2 is
reopened. The RED observer now needs one bounded boolean-expression parser or
equivalent canonical AST for every approved CHECK across all seven tables,
with exhaustive real-MariaDB round trips and mutation sensitivity. Adding one
more expression-specific string rewrite would not close the class of defect.
