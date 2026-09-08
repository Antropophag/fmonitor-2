# Selection schema prefix compatibility correction

Date: 2026-09-05. Append-only correction to unapproved selection storage drafts.
No migration/table exists and no application/database was changed.

The proposed name `fm2_assignment_order_selection_installers` has 41 ASCII
bytes. With the inherited canonical 25-byte prefix it becomes66 bytes, beyond
the existing 64-byte identifier ceiling. Merely lowering the composed prefix
ceiling to23 would make already accepted25-byte installations unupgradeable and
is not authorized by the no-redesign/preservation scope.

Use `fm2_assignment_order_selection_members` in the consolidated candidate.
It has38 ASCII bytes and becomes63 at prefix25. Other proposed table suffixes
are at most39 bytes and remain within64. This is a correction before schema
approval/implementation, not renaming or migrating existing data. Earlier drafts
retain their original bytes as history; the consolidated normative candidate
supersedes this proposed table name only.

The future exact migration contract must preserve composed valid25/invalid26
pre-DB behavior and specify bounded deterministic constraint/index names too;
a safe table name alone does not prove safe automatically derived FK names.
This finding is not Gate1 approval or proof that the full schema is executable.

Independent arithmetic was performed with Python len on literal ASCII names:

```text
41 + 25 = 66  old selection_installers suffix
38 + 25 = 63  new selection_members suffix
```
