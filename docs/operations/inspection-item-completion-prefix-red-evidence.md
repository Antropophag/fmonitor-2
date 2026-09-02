# INSPECTION-ITEM-COMPLETE-001 prefix-validation RED evidence

Date: 2026-09-01

`tests/InstallationProcess/inspection_item_complete_001_prefix_validation_test.php`
constructs the production `ChecklistSync` default composition with an
unconnected `mysqli` and each invalid prefix: 26 ASCII bytes and non-ASCII.
The approved configuration boundary must throw `InvalidArgumentException`
before any database access.

```text
$ tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_prefix_validation_test.php
TestFailure: Invalid production prefix must fail configuration before DB access: aaaaaaaaaaaaaaaaaaaaaaaaaa
RED_ASSERTION: expected failing behavior observed
```

The failure is the missing early configuration rejection, not a mysqli/setup
error. Production and specifications were not edited.
